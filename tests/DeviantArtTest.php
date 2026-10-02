<?php

use App\DeviantArt;
use PHPUnit\Framework\TestCase;

class DeviantArtTest extends TestCase {
  public function testNormalizeStashID() {
    $result = DeviantArt::nomralizeStashID('76dfg312kla');
    self::assertEquals('076dfg312kla', $result);
    $result = DeviantArt::nomralizeStashID('76dfg312kla4');
    self::assertEquals('76dfg312kla4', $result);
    $result = DeviantArt::nomralizeStashID('000adfg312kla4');
    self::assertEquals('0adfg312kla4', $result);
  }
}

it('finds the Location header however the server cases it', function () {
  $target = 'https://www.deviantart.com/illumnious/art/Future-Rainbow-Dash-827347491';
  // DeviantArt answers with a lowercase header name; matching only "Location:" resolved no fav.me link at all
  foreach (["HTTP/1.1 301 Moved Permanently\r\nServer: Apache\r\nlocation: $target\r\nx-backend: web", "HTTP/1.1 301 Moved Permanently\r\nLocation: $target\r\n", "HTTP/1.1 301\nLOCATION:   $target\n"] as $headers)
    expect(\App\HTTP::locationFromHeaders($headers))->toBe($target);

  expect(\App\HTTP::locationFromHeaders("HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n"))->toBeNull();
});
