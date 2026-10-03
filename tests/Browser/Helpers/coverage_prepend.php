<?php

/**
 * auto_prepend_file of the browser/contract test server when COVERAGE_DIR is set (see ServerManager and
 * scripts/coverage.sh). Collects PCOV line coverage per request and folds it into one file per server worker, which
 * scripts/coverage-report.php merges with the unit test coverage.
 *
 * File format: serialize([file => [line => 0 (executable, not run) | 1 (run)]]).
 */

use function pcov\clear;
use function pcov\collect;
use function pcov\start;
use function pcov\stop;
use function pcov\waiting;

(static function () {
  $dir = getenv('COVERAGE_DIR');
  if ($dir === false || $dir === '' || !extension_loaded('pcov'))
    return;

  start();
  // Registered from inside a shutdown function so that it runs after every other shutdown function (and so after the
  // code they run has been measured)
  register_shutdown_function(static function () use ($dir) {
    register_shutdown_function(static function () use ($dir) {
      stop();
      $files = waiting();
      $collected = $files === [] ? [] : collect(\pcov\inclusive, $files);
      clear();

      $path = "$dir/worker-".getmypid().'.ser';
      $state = is_file($path) ? (@unserialize((string)file_get_contents($path), ['allowed_classes' => false]) ?: []) : [];
      foreach ($collected as $file => $lines) {
        foreach ($lines as $line => $count) {
          $state[$file][$line] = ($state[$file][$line] ?? 0) | ($count > 0 ? 1 : 0);
        }
      }
      file_put_contents($path, serialize($state), LOCK_EX);
    });
  });
})();
