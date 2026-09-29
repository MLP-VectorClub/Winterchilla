<?php

namespace App\Testing;

use App\CoreUtils;
use App\File;
use App\JSON;
use Throwable;

/**
 * Shared state for the TEST_MODE-only fake OAuth provider served by TestOAuthController.
 *
 * Authorization codes are self-contained: a prefix plus the base64url-encoded identity the tester
 * approved on the fake consent page. Access/refresh tokens can't be, because the app stores them in
 * length-limited columns sized for DeviantArt's real tokens (50/40 chars), so they're opaque random
 * strings of those lengths mapped to their payload in a small file store. Discord guild membership is
 * kept in the same store, since the app looks it up with a bot token rather than the user's token.
 */
class FakeOAuth {
  public const PROVIDERS = ['deviantart', 'discord'];

  public const CODE_PREFIX = 'fake-code.';

  private const STORE_PATH = FSPATH.'tmp/test-oauth/';

  /** Base URL the app's OAuth clients are pointed at instead of the real provider */
  public static function baseUrl(string $provider):string {
    return ABSPATH."test-oauth/$provider";
  }

  public static function encode(string $prefix, array $payload):string {
    return $prefix.rtrim(strtr(base64_encode(JSON::encode($payload)), '+/', '-_'), '=');
  }

  /**
   * @return array|null The payload, or null if $value isn't a well-formed value with the given prefix
   */
  public static function decode(string $prefix, ?string $value):?array {
    if ($value === null || !str_starts_with($value, $prefix))
      return null;

    $raw = base64_decode(strtr(substr($value, strlen($prefix)), '-_', '+/'), true);
    if ($raw === false)
      return null;
    try {
      $payload = JSON::decode($raw);
    }
    catch (Throwable) {
      return null;
    }

    return is_array($payload) ? $payload : null;
  }

  /**
   * @return array{0: string, 1: string} Access and refresh token, matching DeviantArt's token lengths
   */
  public static function issueTokens(array $payload):array {
    $access = bin2hex(random_bytes(25));
    $refresh = bin2hex(random_bytes(20));
    foreach (['access' => $access, 'refresh' => $refresh] as $type => $token){
      $path = self::STORE_PATH."$type-$token.json";
      CoreUtils::createFoldersFor($path);
      File::put($path, JSON::encode($payload));
    }

    return [$access, $refresh];
  }

  /**
   * @param string $type 'access' or 'refresh'
   *
   * @return array|null The payload the token was issued for
   */
  public static function lookupToken(string $type, ?string $token):?array {
    if ($token === null || !preg_match('/^[a-f\d]+$/', $token))
      return null;
    $path = self::STORE_PATH."$type-$token.json";
    if (!file_exists($path))
      return null;

    return JSON::decode(File::get($path));
  }

  private static function guildMemberPath(string $user_id):string {
    return self::STORE_PATH.'discord-guild-member-'.preg_replace('/\D/', '', $user_id).'.json';
  }

  public static function setGuildMember(string $user_id, ?array $member):void {
    $path = self::guildMemberPath($user_id);
    if ($member === null){
      CoreUtils::deleteFile($path);

      return;
    }
    CoreUtils::createFoldersFor($path);
    File::put($path, JSON::encode($member));
  }

  public static function getGuildMember(string $user_id):?array {
    $path = self::guildMemberPath($user_id);
    if (!file_exists($path))
      return null;

    return JSON::decode(File::get($path));
  }
}
