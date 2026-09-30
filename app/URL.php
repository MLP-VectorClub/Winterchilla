<?php

namespace App;

class URL {
  /**
   * Makes an absolute URL HTTPS
   *
   * @param string $url
   *
   * @return string
   */
  public static function makeHttps($url) {
    // A local server (the test server, a dev machine) usually doesn't speak HTTPS, and upgrading it would break the URL
    if (preg_match('~^http://(?:127\.0\.0\.1|localhost)(?::\d+)?/~', $url))
      return $url;

    return preg_replace('~^(https?:)?//~', 'https://', $url);
  }
}
