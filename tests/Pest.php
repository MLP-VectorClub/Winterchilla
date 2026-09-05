<?php
// Global Pest setup — loaded for all test suites.
// Browser-specific setup is in tests/Browser/Pest.php.
//
// Pest 4's BootFiles bootstrapper only auto-loads this root Pest.php — it does not
// recursively discover nested Pest.php files in subdirectories. Without this explicit
// require, tests/Browser/Pest.php's beforeAll/afterAll (which start/stop the PHP test
// server) never run, and every browser test silently times out trying to reach a server
// that was never started. Guarded to browser-test invocations only, so the rest of the
// suite isn't affected.
if (array_any($_SERVER['argv'] ?? [], fn ($arg) => str_contains($arg, 'tests/Browser'))) {
  require_once __DIR__ . '/Browser/Pest.php';
}
