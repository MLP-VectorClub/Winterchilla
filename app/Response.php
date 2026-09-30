<?php

namespace App;

use RuntimeException;
use function is_array;

class Response {
  /**
   * Responds with a proper HTTP error status and Luna-style body: {"message": "...", ...$extra}
   * (validation failures use 422 with $extra = ['errors' => ['field' => ['msg', ...]]]).
   * This is the target format for the API contract; fail() keeps the legacy 200-ish {status:false} body
   * until the call site is migrated by passing an explicit status.
   */
  public static function error(int $httpStatus, string $message = '', array $extra = []):never {
    if ($message === '')
      $message = HTTP::STATUS_CODES[$httpStatus] ?? 'Error';

    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo JSON::encode(array_merge(['message' => $message], $extra), JSON_UNESCAPED_SLASHES);
    exit;
  }

  /** 401 when signed out, 403 when signed in — for "you may not do this" checks. */
  public static function denied(string $message = ''):never {
    self::error(Auth::$signed_in ? 403 : 401, $message);
  }

  /**
   * 422 in Laravel's validation format: {"message": "The given data was invalid.", "errors": {field: [msg]}}
   */
  public static function invalid(string $field, string $message):never {
    self::error(422, 'The given data was invalid.', ['errors' => [$field => [$message]]]);
  }

  /** Responds 2xx with the given data as the body, without the legacy status envelope. */
  public static function ok(array $data = [], int $httpStatus = 200):never {
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo JSON::encode($data, JSON_UNESCAPED_SLASHES);
    exit;
  }

  public static function noContent():never {
    http_response_code(204);
    exit;
  }

  /**
   * Empty message resolves to 401 when signed out and 403 when signed in. Passing $status (during the
   * migration to HTTP statuses) switches the response to the error() format.
   */
  public static function fail(string $message = '', $data = [], bool $prettyPrint = false, ?int $status = null):never {
    if ($status !== null)
      self::error($status, $message, is_array($data) ? $data : []);

    if (empty($message)){
      $message = Auth::$signed_in ? 'Insufficient permissions.'
        : '<p>You are not signed in (or your session expired).</p><p class="align-center"><button class="typcn green btn-da da-login" id="turbo-sign-in" data-url="/da-auth/begin">Sign back in</button></p>';
    }

    self::_respond(false, $message, $data, $prettyPrint);
  }

  public static function failApi(string $message = '', $data = [], bool $prettyPrint = false):never {
    if (empty($message)){
      $message = Auth::$signed_in
        ? 'You do not have permission to access the requested resource'
        : 'The requested resource requires authentication';
    }

    self::_respond(false, $message, $data, $prettyPrint);
  }

  public static function dbError(string $message = '', bool $pretty_print = false, ?int $status = null):never {
    if (!empty($message))
      $message .= ': ';
    $message .= rtrim('Error while saving to database: '.DB::$instance->getLastError(), ': ');

    if ($status !== null)
      self::error($status, $message);

    self::_respond(false, $message, [], $pretty_print);
  }

  public static function success(string $message, $data = [], bool $pretty_print = false):never {
    self::_respond(true, $message, $data, $pretty_print);
  }

  public static function done(array $data = [], ?string $cache_key = null, ?int $cache_for_seconds = null):never {
    if ($cache_key !== null) {
      if ($cache_for_seconds === null)
        throw new RuntimeException("Cache duration for key $cache_key is null");
      $data['cachedOn'] = date('c');
      $data['cachedFor'] = $cache_for_seconds;
    }
    self::_respond(true, '', $data, false, $cache_key, $cache_for_seconds);
  }

  /**
   * Responds 200 with $data and caches the encoded body in Redis for $cache_for_seconds. Serve it with doneCached().
   */
  public static function okCached(array $data, string $cache_key, int $cache_for_seconds):never {
    $json = JSON::encode($data, JSON_UNESCAPED_SLASHES);
    http_response_code(200);
    header('Content-Type: application/json');
    RedisHelper::set($cache_key, serialize($json), $cache_for_seconds);
    echo $json;
    exit;
  }

  public static function doneCached(string $data):never {
    header('Content-Type: application/json');
    self::_respondWith(unserialize($data, [false]));
  }

  private static function _respondWith(string $data, ?string $cache_key = null, ?int $cache_for_seconds = null):never {
    if ($cache_key !== null) {
      RedisHelper::set($cache_key, serialize($data), $cache_for_seconds);
    }
    echo $data;
    exit;
  }

  private static function _respond(bool $status, string $message, $data, bool $prettyPrint, ?string $cache_key = null, ?int $cache_for = null):never {
    header('Content-Type: application/json');
    $response = ['status' => $status];
    if (!empty($message))
      $response['message'] = $message;
    if (!empty($data) && is_array($data))
      $response = array_merge($data, $response);
    $mask = JSON_UNESCAPED_SLASHES;
    if ($prettyPrint)
      $mask |= JSON_PRETTY_PRINT;
    self::_respondWith(JSON::encode($response, $mask), $cache_key, $cache_for);
  }
}
