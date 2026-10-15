<?php

namespace App;

use App\Models\User;
use PDO;
use Throwable;

/**
 * Read-only mode: the site is a window onto Luna's database, and the visitors who are signed in on the new site (their session lives in Luna's `luna_sessions`
 * table, the cookie of it is shared by every subdomain) should appear signed in here as well. Nothing is written: the cookie is Laravel's encrypted
 * session cookie, which is decrypted with Luna's `APP_KEY` (`LUNA_APP_KEY`), the session is looked up with the read-only database role, and the user of it
 * is the one the page is rendered for. The `SESSION_COOKIE` of Luna's `.env` is the name of the cookie
 */
class LunaSession {
  /** Minutes a session lasts without a request, Luna's `SESSION_LIFETIME` */
  private const DEFAULT_LIFETIME = 43200;

  /**
   * The session id inside a Laravel session cookie, `null` for anything that is not one made with this key (a wrong key, another cookie name, a
   * damaged cookie)
   */
  public static function sessionIdFromCookie(string $cookie, string $cookie_name, string $app_key):?string {
    if (str_starts_with($app_key, 'base64:'))
      $app_key = base64_decode(substr($app_key, 7), true);
    if (!is_string($app_key) || strlen($app_key) !== 32)
      return null;

    $payload = json_decode(base64_decode($cookie, true) ?: '', true);
    if (!is_array($payload))
      return null;
    foreach (['iv', 'value', 'mac'] as $part){
      if (!isset($payload[$part]) || !is_string($payload[$part]))
        return null;
    }

    // The same check Laravel's encrypter makes before it decrypts anything
    if (!hash_equals(hash_hmac('sha256', $payload['iv'].$payload['value'], $app_key), $payload['mac']))
      return null;
    $iv = base64_decode($payload['iv'], true);
    if ($iv === false)
      return null;
    $decrypted = openssl_decrypt($payload['value'], 'aes-256-cbc', $app_key, 0, $iv);
    if ($decrypted === false)
      return null;

    // Laravel puts a prefix made from the cookie's name in front of the value, so a cookie cannot be passed off as another one
    $prefix = hash_hmac('sha1', $cookie_name.'v2', $app_key).'|';
    if (!str_starts_with($decrypted, $prefix))
      return null;
    $id = substr($decrypted, strlen($prefix));

    return preg_match('/^[A-Za-z0-9]{40}$/', $id) === 1 ? $id : null;
  }

  /** Whether a session that was last used at this time (a Unix timestamp) is still alive */
  public static function isAlive(int $last_activity, int $lifetime_minutes, ?int $now = null):bool {
    return $last_activity + $lifetime_minutes * 60 >= ($now ?? time());
  }

  /** The user signed in on the new site with the browser that makes this request, `null` for everybody else */
  public static function user():?User {
    $cookie_name = CoreUtils::env('SESSION_COOKIE');
    $app_key = CoreUtils::env('LUNA_APP_KEY');
    if (empty($cookie_name) || empty($app_key) || empty($_COOKIE[$cookie_name]))
      return null;

    $id = self::sessionIdFromCookie((string)$_COOKIE[$cookie_name], $cookie_name, $app_key);
    if ($id === null)
      return null;

    try {
      $values = [$id];
      $stmt = User::connection()->query('SELECT user_id, last_activity FROM luna_sessions WHERE id = ? LIMIT 1', $values);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!is_array($row) || empty($row['user_id']))
        return null;
      $lifetime = (int)(CoreUtils::env('LUNA_SESSION_LIFETIME') ?: self::DEFAULT_LIFETIME);
      if (!self::isAlive((int)$row['last_activity'], $lifetime))
        return null;

      return User::find((int)$row['user_id']);
    } catch (Throwable $e){
      // The table is Luna's: where there is none (a copy of the site without it) nobody is signed in this way
      return null;
    }
  }
}
