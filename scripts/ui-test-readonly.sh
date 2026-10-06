#!/usr/bin/env bash
# Runs the read-only mode tests: the test server starts with READ_ONLY=true (database session read-only, changes refused, no writes while pages are
# viewed). Extra arguments go to pest.
set -euo pipefail
cd "$(dirname "$0")/.."
export READ_ONLY=true
exec vendor/bin/pest tests/Browser/ReadOnly "$@"
