<?php

use App\RouteNames;
use PHPUnit\Framework\TestCase;

class RouteNamesTest extends TestCase {
  /** Same shape the websocket server accepts for a route name (Muffins, src/metrics.ts) */
  private const ROUTE_NAME = '~^(/([a-z0-9][a-z0-9-]{0,23}|\[{1,2}(\.{3})?[A-Za-z0-9_]{1,24}\]{1,2})){1,6}$~';

  private static function names():array {
    return require CONFPATH.'route_names.php';
  }

  public function testEveryNameIsARealRoutePattern():void {
    $routes = file_get_contents(CONFPATH.'routes/pages.php');
    preg_match_all("~\\\$page_route\\('([^']+)'~", $routes, $matches);
    foreach (array_keys(self::names()) as $pattern)
      self::assertContains($pattern, $matches[1], "'$pattern' is not a route of routes/pages.php (renamed or removed?)");
  }

  public function testEveryNameLooksLikeACelestiaRoute():void {
    foreach (self::names() as $pattern => $name){
      $filled = str_replace('{first}', 'movie', $name);
      if ($name === '/')
        continue;
      self::assertMatchesRegularExpression(self::ROUTE_NAME, $filled, "$pattern -> $name");
    }
  }

  public function testNamesFollowCelestiaWhereThePageExistsInBoth():void {
    self::assertSame('/cg/[guide]', RouteNames::resolve('/[cg]/[guide:guide]?/[i]?', '/cg/pony/2'));
    self::assertSame('/users/[user]/cg/point-history', RouteNames::resolve('/users/[i:user_id]/[cg]/point-history/[i]?', '/users/5/cg/point-history'));
  }

  public function testTheFirstSegmentIsFilledInForPageTypes():void {
    self::assertSame('/movie/[id]', RouteNames::resolve('/[st]/[i:id][adi]?', '/movie/12-title'));
    self::assertSame('/settings', RouteNames::resolve('/[sett]', '/settings'));
  }

  public function testAnAddressIsNeverPutInTheName():void {
    self::assertNull(RouteNames::resolve('/[st]/[i:id][adi]?', '/Mo%20vie/12'));
    self::assertNull(RouteNames::resolve('/[sett]', '/' . str_repeat('a', 40)));
  }

  public function testPagesWithoutACounterpartHaveNoName():void {
    self::assertNull(RouteNames::resolve('/manifest', '/manifest'));
    self::assertNull(RouteNames::resolve(null, '/anything/at/all'));
  }
}
