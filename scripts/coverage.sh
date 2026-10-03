#!/usr/bin/env bash
# Full code coverage of app/: the unit tests plus everything the browser and contract tests make the test server run, merged into
# one report (build/coverage/html/index.html, build/coverage/clover.xml, summary on stdout).
#
#   scripts/coverage.sh                 # unit + browser/contract tests
#   scripts/coverage.sh --unit-only     # skip the (slow) browser/contract tests
#
# Needs a coverage driver: PCOV, either loaded by php.ini or built as a shared module (no distro package for PHP 8.5 here):
#   git clone --depth 1 https://github.com/krakjoe/pcov && cd pcov && phpize && ./configure --enable-pcov && make
#   cp modules/pcov.so ~/.local/lib/php/pcov.so        # or set PCOV_SO=/path/to/pcov.so
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_FLAGS=()
if ! php -m | grep -qix pcov; then
  PCOV_SO="${PCOV_SO:-$HOME/.local/lib/php/pcov.so}"
  if [ ! -f "$PCOV_SO" ]; then
    echo "No PCOV: load the extension in php.ini or point PCOV_SO at pcov.so (see the header of this script)" >&2
    exit 1
  fi
  export PCOV_SO
  PHP_FLAGS+=(-d "extension=$PCOV_SO")
fi
PHP_FLAGS+=(-d pcov.enabled=1 -d "pcov.directory=$PWD/app")

rm -rf build/coverage
mkdir -p build/coverage/http

echo "== Unit tests"
php "${PHP_FLAGS[@]}" vendor/bin/pest --coverage-php build/coverage/unit.cov

if [ "${1:-}" != "--unit-only" ]; then
  echo "== Browser and contract tests (server-side coverage)"
  COVERAGE_DIR="$PWD/build/coverage/http" vendor/bin/pest tests/Browser
fi

echo "== Report"
php "${PHP_FLAGS[@]}" scripts/coverage-report.php
