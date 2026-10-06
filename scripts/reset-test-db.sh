#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  echo "Error: .env file not found" >&2
  exit 1
fi

# A TEST_DB_NAME given in the environment wins over the one in .env (the suite passes it on, so it can run against another database)
ENV_TEST_DB_NAME="${TEST_DB_NAME:-}"
# TEST_SCHEMA=luna builds the tables with Luna's migrations (../Luna, or LUNA_DIR) instead of phinx, to check Winterchilla against the schema both
# applications are meant to share
TEST_SCHEMA="${TEST_SCHEMA:-winterchilla}"
LUNA_DIR="${LUNA_DIR:-$PWD/../Luna}"

# Load .env without exporting (just to read variables)
set -a
source .env
set +a

DB="${ENV_TEST_DB_NAME:-${TEST_DB_NAME:-winterchilla_test}}"
HOST="${DB_HOST:-localhost}"
USER="${DB_USER:-winterchilla}"
export PGPASSWORD="${DB_PASS}"

PSQL="psql -h $HOST -U $USER"

echo "Resetting test database: $DB"
$PSQL -d postgres -c "DROP DATABASE IF EXISTS \"$DB\";"
$PSQL -d postgres -c "CREATE DATABASE \"$DB\";"
$PSQL -d "$DB" -f setup/create_extensions.pg.sql

if [ "$TEST_SCHEMA" = "luna" ]; then
  (cd "$LUNA_DIR" && DB_DATABASE="$DB" APP_CONFIG_CACHE="$(mktemp -u)" php artisan migrate --force | tail -1)
else
  DB_NAME="$DB" vendor/bin/phinx migrate
fi
DB_NAME="$DB" vendor/bin/phinx seed:run

# Reset sequences so new rows don't collide with explicitly-seeded IDs. Appearances created by tests start at 900101: their
# sprite/render files live in fs/, which the dev site shares, and low IDs would pick up that site's stale files for the same ID.
$PSQL -d "$DB" -c "
  SELECT setval(pg_get_serial_sequence('users', 'id'),        (SELECT COALESCE(MAX(id), 1) FROM users));
  SELECT setval(pg_get_serial_sequence('show', 'id'),         (SELECT COALESCE(MAX(id), 1) FROM show));
  SELECT setval(pg_get_serial_sequence('appearances', 'id'),  (SELECT GREATEST(COALESCE(MAX(id), 1), 900100) FROM appearances));
  SELECT setval(pg_get_serial_sequence('events', 'id'),       (SELECT COALESCE(MAX(id), 1) FROM events));
"

# Tokens and Discord guild memberships issued by the fake OAuth provider (TestOAuthController)
rm -rf fs/tmp/test-oauth

echo "Done."
