#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  echo "Error: .env file not found" >&2
  exit 1
fi

# Load .env without exporting (just to read variables)
set -a
source .env
set +a

DB="${TEST_DB_NAME:-winterchilla_test}"
HOST="${DB_HOST:-localhost}"
USER="${DB_USER:-winterchilla}"
export PGPASSWORD="${DB_PASS}"

PSQL="psql -h $HOST -U $USER"

echo "Resetting test database: $DB"
$PSQL -d postgres -c "DROP DATABASE IF EXISTS \"$DB\";"
$PSQL -d postgres -c "CREATE DATABASE \"$DB\";"
$PSQL -d "$DB" -f setup/create_extensions.pg.sql

DB_NAME="$DB" vendor/bin/phinx migrate
DB_NAME="$DB" vendor/bin/phinx seed:run

echo "Done."
