<?php

namespace Tests\Browser\Helpers;

/**
 * In TEST_MODE the app never asks DeviantArt whether a deviation was accepted into the group gallery; it looks for a
 * marker file named after the deviation ID instead (see CoreUtils::isDeviationInClub). These helpers manage the markers.
 */
class ClubGallery {
  private static function dir():string {
    return dirname(__DIR__, 3) . '/fs/tmp/test-club-gallery/';
  }

  public static function accept(string $deviationId):void {
    if (!is_dir(self::dir()))
      mkdir(self::dir(), 0777, true);
    touch(self::dir() . $deviationId);
  }

  public static function reject(string $deviationId):void {
    @unlink(self::dir() . $deviationId);
  }
}
