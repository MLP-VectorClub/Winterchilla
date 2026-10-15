#!/usr/bin/env bash
# Runs the read-only mode tests: the test server starts with READ_ONLY=true (database session read-only, changes refused, no writes while pages are
# viewed). Extra arguments go to pest.
set -euo pipefail
cd "$(dirname "$0")/.."
export READ_ONLY=true READ_ONLY_URL=https://new.example
# Luna's session cookie (its name and its APP_KEY), for the test of being signed in by the new site: the key is the one of tests/LunaSessionTest.php
# A small limit for the test of the limit on invalid tokens (App\InvalidTokenLimiter)
export INVALID_TOKEN_LIMIT=5
export SESSION_COOKIE=test_session LUNA_APP_KEY=base64:a2tra2tra2tra2tra2tra2tra2tra2tra2tra2tra2s=
exec vendor/bin/pest tests/Browser/ReadOnly "$@"
