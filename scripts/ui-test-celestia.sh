#!/usr/bin/env bash
# Runs the UI tests (Admin, User, Guest) against the local Celestia + Luna test instances: Celestia on :3000 (E2E_TEST_LOGIN=1), Luna's contract
# server on :8766. The browser flows are driven through Celestia; the tests' own data setup goes straight to Luna's API with a bearer token.
# Extra arguments go to pest, e.g. scripts/ui-test-celestia.sh --filter "tag"
set -euo pipefail
cd "$(dirname "$0")/.."

export UI_BASE_URL="${UI_BASE_URL:-http://localhost:3000}"
export CONTRACT_BASE_URL="${CONTRACT_BASE_URL:-http://127.0.0.1:8766}"
export CONTRACT_API_PATH="${CONTRACT_API_PATH-}"
export CONTRACT_AUTH="${CONTRACT_AUTH:-bearer}"
export CONTRACT_LOGIN_URL="${CONTRACT_LOGIN_URL:-/test/login/{id}}"
export CONTRACT_LOGIN_METHOD="${CONTRACT_LOGIN_METHOD:-POST}"

# The tests assume the seeded state (a vote not cast yet, a free personal guide slot, ...) and leave data behind when they fail, so start from a
# freshly loaded seed. UI_RESET=0 skips it; the loader is Luna's scripts/load-contract-seed.sh (needs LUNA_DIR, default ../Luna).
if [ "${UI_RESET:-1}" != "0" ]; then
  ROOT="$PWD"
  LUNA_DIR="${LUNA_DIR:-$ROOT/../Luna}"
  [ -f build/contract-seed.sql ] || scripts/dump-contract-seed.sh > build/contract-seed.sql
  echo "== Reloading the contract seed into Luna's luna_contract database"
  (cd "$LUNA_DIR" && scripts/load-contract-seed.sh "$ROOT/build/contract-seed.sql" >/dev/null)
fi

exec vendor/bin/pest tests/Browser/Admin tests/Browser/User tests/Browser/Guest --exclude-group=winterchilla-only "$@"
