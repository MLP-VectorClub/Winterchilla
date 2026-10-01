#!/usr/bin/env bash
# Dumps the seeded contract-test database (users 9001-9006, posts, appearances, personal guides, events, Discord members, ...)
# as plain INSERT statements, so another implementation of the API (Luna) can load the same data into its own database and run
# tests/Browser/Api against it (see the CONTRACT_* variables in tests/Browser/Helpers/ApiClient.php).
#
#   scripts/dump-contract-seed.sh > contract-seed.sql
#
# Resets the test database first, so the dump is exactly what the suite starts from. The files the seeds put on disk (cutie mark
# SVG under fs/cm_source) and the Redis-cached deviations are not part of the dump.
set -euo pipefail

cd "$(dirname "$0")/.."
set -a
source .env
set +a

DB="${TEST_DB_NAME:-winterchilla_test}"
bash scripts/reset-test-db.sh >&2
PGPASSWORD="${DB_PASS}" pg_dump -h "${DB_HOST:-localhost}" -U "${DB_USER:-winterchilla}" -d "$DB" \
  --data-only --column-inserts --disable-triggers --no-owner -T phinxlog
