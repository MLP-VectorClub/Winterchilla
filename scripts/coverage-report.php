<?php

/**
 * Merges the unit test coverage (build/coverage/unit.cov, from `pest --coverage-php`) with the coverage the browser and
 * contract tests produced in the test server (build/coverage/http/worker-*.ser) and writes the reports:
 * build/coverage/html, build/coverage/clover.xml and a text summary on stdout.
 *
 * Run through scripts/coverage.sh.
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Report\Clover;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as Html;
use SebastianBergmann\CodeCoverage\Report\Text;
use SebastianBergmann\CodeCoverage\Report\Thresholds;

$root = dirname(__DIR__);
require "$root/vendor/autoload.php";

$dir = "$root/build/coverage";
$unitFile = "$dir/unit.cov";
if (!is_file($unitFile))
  fwrite(STDERR, "No unit coverage at $unitFile, reporting the HTTP tests alone is not supported\n") && exit(1);

/** @var CodeCoverage $coverage */
$coverage = require $unitFile;

$lines = [];
foreach (glob("$dir/http/worker-*.ser") ?: [] as $file) {
  foreach (unserialize((string)file_get_contents($file), ['allowed_classes' => false]) ?: [] as $source => $fileLines) {
    foreach ($fileLines as $line => $run)
      $lines[$source][$line] = ($lines[$source][$line] ?? 0) | $run;
  }
}
if ($lines === [])
  fwrite(STDERR, "No HTTP coverage found in $dir/http (did the browser tests run with COVERAGE_DIR set?)\n");
else {
  $raw = [];
  foreach ($lines as $source => $fileLines) {
    foreach ($fileLines as $line => $run)
      $raw[$source][$line] = $run ? 1 : -1;
  }
  $coverage->append(RawCodeCoverageData::fromXdebugWithoutPathCoverage($raw), 'browser and contract tests');
}

$thresholds = Thresholds::from(50, 90);
(new Html('Winterchilla', null, $thresholds))->process($coverage, "$dir/html");
(new Clover())->process($coverage, "$dir/clover.xml");
echo (new Text($thresholds, false, false))->process($coverage, false);
echo "\nHTML report: $dir/html/index.html\n";
