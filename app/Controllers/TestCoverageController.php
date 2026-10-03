<?php

namespace App\Controllers;

use App\CoreUtils;

/**
 * TEST_MODE-only collector for the browser-side coverage of the page scripts (see scripts/coverage.sh and assets/coverage-reporter.js).
 * Like the fake OAuth provider it skips the parent constructor: the instrumented pages post here from sendBeacon, without a CSRF token.
 */
class TestCoverageController extends Controller {
  public function __construct() {
    if (!CoreUtils::env('TEST_MODE'))
      CoreUtils::notFound();
  }

  public function scriptHits():void {
    $dir = getenv('COVERAGE_JS_DIR');
    $id = $_GET['id'] ?? '';
    if ($dir === false || $dir === '' || !preg_match('/^[a-z0-9]{8,40}$/', $id))
      CoreUtils::notFound();

    $body = file_get_contents('php://input', length: 8 * 1024 * 1024);
    if ($body === false || !json_validate($body))
      CoreUtils::notFound();

    if (!is_dir($dir))
      mkdir($dir, 0777, true);
    // One file per page load; every post is the whole state of that page so far, so the latest one wins
    file_put_contents("$dir/$id.json", $body, LOCK_EX);
    http_response_code(204);
    exit;
  }
}
