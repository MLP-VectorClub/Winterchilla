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

DB="${DB_NAME:-winterchilla}"
HOST="${DB_HOST:-localhost}"
USER="${DB_USER:-winterchilla}"
export PGPASSWORD="${DB_PASS}"

PSQL="psql -h $HOST -U $USER"

echo "Resetting database: $DB"
$PSQL -d postgres -c "DROP DATABASE IF EXISTS \"$DB\";"
$PSQL -d postgres -c "CREATE DATABASE \"$DB\";"
$PSQL -d "$DB" -f setup/create_extensions.pg.sql

vendor/bin/phinx migrate

DATA_FILE="setup/mlpvc-rr_data.pg.sql"
if [ -f "$DATA_FILE" ]; then
  read -r -p "Import $DATA_FILE into $DB? [y/N] " confirm
  if [[ "$confirm" =~ ^[Yy]$ ]]; then
    $PSQL -d "$DB" -f "$DATA_FILE"
    echo "Data imported."
  fi
fi

echo "Done."
