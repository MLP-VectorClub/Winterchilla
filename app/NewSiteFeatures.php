<?php

namespace App;

/**
 * The features listed in `config/new_site_only.php`, which only exist on the new site
 */
class NewSiteFeatures {
  /**
   * @return array<string, array{title: string, summary: string, routes: string[], new_path?: string}>
   */
  public static function all():array {
    return require CONFPATH.'new_site_only.php';
  }

  /**
   * @return array{title: string, summary: string, routes: string[], new_path?: string}|null
   */
  public static function find(string $key):?array {
    return self::all()[$key] ?? null;
  }

  /**
   * Where the feature is on the new site for a request, `null` when the new site's address is not known (`READ_ONLY_URL`)
   *
   * @param array<string, mixed> $params route parameters of the request
   */
  public static function newSiteUrl(array $feature, array $params, string $requested_uri):?string {
    $base = CoreUtils::env('READ_ONLY_URL');
    if (!is_string($base) || $base === '')
      return null;

    $path = strtok($requested_uri, '?');
    $query = parse_url($requested_uri, PHP_URL_QUERY);
    if (isset($feature['new_path'])){
      $path = preg_replace_callback('/\{(\w+)}/', fn($m) => rawurlencode((string)($params[$m[1]] ?? '')), $feature['new_path']);
    }

    return rtrim($base, '/').$path.(is_string($query) && $query !== '' ? "?$query" : '');
  }
}
