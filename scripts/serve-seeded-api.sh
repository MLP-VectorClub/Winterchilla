#!/usr/bin/env bash
# Starts a second, throwaway Winterchilla API with the contract-test seed data, on its own port and its own database, so it
# can run next to the test suite (which owns port 8765 and the `winterchilla_test` database) without touching either.
#
#   scripts/serve-seeded-api.sh [port] [database]      # defaults: 8766 winterchilla_api_local
#
# Then e.g. `curl http://127.0.0.1:8766/api/v0/config` or sign in as a seeded user with `/test-login/9001` (user), `/9002`
# (admin), `/9003` (fresh user). The server runs in TEST_MODE, so it has the test-only routes and never talks to the real
# DeviantArt. Stop it with Ctrl+C; the database is dropped and recreated on the next start.
#
# Shared with the test suite and therefore worth knowing about: Redis (the seeded deviation cache keys are the same ones the
# tests use), the `fs/` directory, and the seeded post/entry image URLs, which point at http://127.0.0.1:8765 (the test server's
# port), so those images only load while a test run, or `php -S 127.0.0.1:8765 -t public`, is serving them.
set -euo pipefail

cd "$(dirname "$0")/.."

PORT="${1:-8766}"
DB="${2:-winterchilla_api_local}"

if [ ! -f .env ]; then
  echo "Error: .env file not found" >&2
  exit 1
fi
set -a
source .env
set +a

if [ "$DB" = "${TEST_DB_NAME:-winterchilla_test}" ] || [ "$PORT" = "8765" ]; then
  echo "Refusing to use the test suite's database or port; pick others" >&2
  exit 1
fi

export PGPASSWORD="${DB_PASS}"
PSQL="psql -h ${DB_HOST:-localhost} -U ${DB_USER:-winterchilla}"

echo "Creating database $DB" >&2
$PSQL -d postgres -c "DROP DATABASE IF EXISTS \"$DB\";" >&2
$PSQL -d postgres -c "CREATE DATABASE \"$DB\";" >&2
$PSQL -d "$DB" -f setup/create_extensions.pg.sql >&2
DB_NAME="$DB" vendor/bin/phinx migrate >&2
DB_NAME="$DB" vendor/bin/phinx seed:run >&2

echo "Serving http://127.0.0.1:$PORT (database $DB)" >&2
TEST_MODE=true TEST_DB_NAME="$DB" APP_URL="http://127.0.0.1:$PORT" PHP_CLI_SERVER_WORKERS=4 \
  exec php -d variables_order=EGPCS -d opcache.revalidate_freq=0 -S "127.0.0.1:$PORT" -t public
