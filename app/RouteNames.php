<?php

namespace App;

/**
 * The name of the page being served in Celestia's terms (`/cg/[guide]`, see `config/route_names.php`), sent to the websocket server by the page's script.
 * Only the name of the matched route is ever used, never the address, so no ids, user names or search terms can end up in the visitor statistics.
 */
class RouteNames {
  /** The name of the current page, null for pages that have no counterpart in Celestia */
  public static ?string $current = null;

  /** @var array<string,string>|null */
  private static ?array $names = null;

  /**
   * @param string|null $pattern The route pattern that matched (AltoRouter's route name, see `routes/pages.php`)
   * @param string $uri The requested path, only used for the first segment of routes whose name contains `{first}`
   */
  public static function resolve(?string $pattern, string $uri):?string {
    self::$names ??= require CONFPATH.'route_names.php';
    $name = $pattern === null ? null : (self::$names[$pattern] ?? null);
    if ($name !== null && str_contains($name, '{first}')){
      $first = explode('/', ltrim($uri, '/'))[0];
      // The segment was matched by a fixed list in the router (page types, `u`, `settings`): anything else is not trusted into the name
      $name = preg_match('~^[a-z]{1,12}$~', $first) === 1 ? str_replace('{first}', $first, $name) : null;
    }
    return $name;
  }
}
