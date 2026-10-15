<?php

namespace App;

use Redis;

/**
 * Keeps whoever throws made up session tokens at the site from going on forever, hoping that one of them turns out to be a real one. Every token that
 * is not one (a cookie that does not decrypt, a session of ours that does not exist) is counted against the visitor's address; past the limit, requests
 * that carry a token are refused with 429 until the window of the count is over, before any token is looked up. Requests without a token are not
 * affected, so a visitor behind the same address is only held up by their own stale cookie.
 *
 * Real tokens cannot be guessed this way in the first place (Luna's cookies are authenticated before anything is looked up, ours are 256 bit), this
 * is for the load and the noise.
 */
class InvalidTokenLimiter {
  /** How many invalid tokens an address may present in a window (`INVALID_TOKEN_LIMIT`) */
  public const DEFAULT_LIMIT = 30;
  /** How long a window lasts, in seconds (`INVALID_TOKEN_WINDOW`) */
  public const DEFAULT_WINDOW = 600;

  private static ?object $store = null;

  /** For tests: where the counts are kept, anything with `increment(string $key, int $ttl): int`, `count(string $key): int` and `ttl(string $key): int` */
  public static function useStore(?object $store):void {
    self::$store = $store;
  }

  private static function store():?object {
    if (self::$store !== null)
      return self::$store;
    $redis = RedisHelper::getInstance();
    if ($redis === null)
      return null;

    return self::$store = new class($redis) {
      public function __construct(private Redis $redis) {}

      public function increment(string $key, int $ttl):int {
        $count = (int)$this->redis->incr($key);
        if ($count === 1)
          $this->redis->expire($key, $ttl);

        return $count;
      }

      public function count(string $key):int {
        return (int)$this->redis->get($key);
      }

      public function ttl(string $key):int {
        return max(0, (int)$this->redis->ttl($key));
      }
    };
  }

  private static function key(string $address):string {
    return 'invalid-tokens:'.$address;
  }

  private static function limit():int {
    return (int)(CoreUtils::env('INVALID_TOKEN_LIMIT') ?: self::DEFAULT_LIMIT);
  }

  private static function window():int {
    return (int)(CoreUtils::env('INVALID_TOKEN_WINDOW') ?: self::DEFAULT_WINDOW);
  }

  /** Seconds until the address may present tokens again, `null` when it may now (and also when there is nowhere to keep the counts: no Redis, no limit) */
  public static function retryAfter(string $address):?int {
    $store = self::store();
    if ($store === null || $store->count(self::key($address)) < self::limit())
      return null;

    return max(1, $store->ttl(self::key($address)));
  }

  /** Counts a token that was not one */
  public static function recordInvalid(string $address):void {
    self::store()?->increment(self::key($address), self::window());
  }

  /** Refuses the request when the address is over the limit: call it before anything looks at a token */
  public static function guard(string $address):void {
    $retry_after = self::retryAfter($address);
    if ($retry_after === null)
      return;

    http_response_code(429);
    header("Retry-After: $retry_after");
    header('Content-Type: text/plain; charset=utf-8');
    echo "Too many invalid sign-in tokens from this address, please try again in $retry_after seconds.";
    exit;
  }
}
