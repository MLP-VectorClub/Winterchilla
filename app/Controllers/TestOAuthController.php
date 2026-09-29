<?php

namespace App\Controllers;

use App\CoreUtils;
use App\HTTP;
use App\JSON;
use App\Testing\FakeOAuth;
use App\Twig;

/**
 * TEST_MODE-only stand-in for the DeviantArt and Discord OAuth providers, so browser tests can drive the
 * real /da-auth and /discord-connect flows end to end. In TEST_MODE the app's OAuth clients are pointed
 * here (see FakeOAuth::baseUrl()); URL paths mirror the real providers' where practical.
 *
 * Deliberately doesn't call the parent constructor: this plays an external service, so the app's own
 * CSRF protection and session handling must not apply (the token endpoint is POSTed to server-side).
 */
class TestOAuthController extends Controller {
  private const SCOPES = [
    'deviantart' => 'browse user',
    'discord' => 'identify guilds',
  ];
  private const TOKEN_LIFETIME = [
    'deviantart' => 3600,
    'discord' => 604800,
  ];

  public function __construct() {
    if (!CoreUtils::env('TEST_MODE'))
      CoreUtils::notFound();
  }

  private static function provider(array $params):string {
    $provider = $params['provider'] ?? null;
    if (!in_array($provider, FakeOAuth::PROVIDERS, true))
      CoreUtils::notFound();

    return $provider;
  }

  private static function json(int $status, array $data):never {
    HTTP::statusCode($status);
    header('Content-Type: application/json');
    echo JSON::encode($data);
    exit;
  }

  /** Only ever send the browser back to this app, even in test mode */
  private static function validRedirectUri(?string $uri):string {
    if (empty($uri) || !str_starts_with($uri, ABSPATH)){
      HTTP::statusCode(400);
      header('Content-Type: text/plain');
      die('Fake OAuth provider: missing or foreign redirect_uri');
    }

    return $uri;
  }

  /** GET /test-oauth/[provider]/oauth2/authorize — the consent screen */
  public function authorize(array $params):void {
    $provider = self::provider($params);
    if (($_GET['response_type'] ?? null) !== 'code' || empty($_GET['state'])){
      HTTP::statusCode(400);
      header('Content-Type: text/plain');
      die('Fake OAuth provider: expected response_type=code and a state');
    }

    // Like DeviantArt's sign-in pages: severs the link to the sign-in popup's opener (see $.openAuthPopup)
    header('Cross-Origin-Opener-Policy: same-origin');
    Twig::display('test/oauth-authorize', [
      'provider' => $provider,
      'redirect_uri' => self::validRedirectUri($_GET['redirect_uri'] ?? null),
      'state' => $_GET['state'],
      'scope' => $_GET['scope'] ?? '',
    ]);
  }

  /** GET /test-oauth/[provider]/decide — where the consent screen's buttons submit to */
  public function decide(array $params):void {
    $provider = self::provider($params);
    $redirect_uri = self::validRedirectUri($_GET['redirect_uri'] ?? null);
    $query = ['state' => $_GET['state'] ?? ''];

    switch ($_GET['decision'] ?? null){
      case 'approve':
        $identity = $provider === 'deviantart'
          ? [
            'userid' => strtoupper(trim($_GET['userid'] ?? '')),
            'username' => trim($_GET['username'] ?? ''),
          ]
          : [
            'id' => trim($_GET['id'] ?? ''),
            'username' => trim($_GET['username'] ?? ''),
            'global_name' => trim($_GET['global_name'] ?? '') ?: null,
          ];
        if ($provider === 'discord'){
          FakeOAuth::setGuildMember($identity['id'], isset($_GET['in_guild']) ? [
            'user' => ['id' => $identity['id'], 'username' => $identity['username']],
            'nick' => trim($_GET['nick'] ?? '') ?: null,
            'roles' => [],
            'joined_at' => '2020-01-01T00:00:00.000000+00:00',
            'deaf' => false,
            'mute' => false,
          ] : null);
        }
        $query['code'] = FakeOAuth::encode(FakeOAuth::CODE_PREFIX, ['provider' => $provider, 'identity' => $identity]);
      break;
      // Approve, but hand back a code the token endpoint will reject (server-side exchange failure)
      case 'invalid_code':
        $query['code'] = 'not-a-valid-code';
      break;
      default:
        $query['error'] = 'access_denied';
        $query['error_description'] = 'The user has denied your application access.';
    }

    HTTP::tempRedirect($redirect_uri.(str_contains($redirect_uri, '?') ? '&' : '?').http_build_query($query));
  }

  /** POST /test-oauth/deviantart/oauth2/token and /test-oauth/discord/api/oauth2/token */
  public function token(array $params):void {
    $provider = self::provider($params);

    $payload = match ($_POST['grant_type'] ?? null) {
      'authorization_code' => FakeOAuth::decode(FakeOAuth::CODE_PREFIX, $_POST['code'] ?? null),
      'refresh_token' => FakeOAuth::lookupToken('refresh', $_POST['refresh_token'] ?? null),
      default => self::json(400, ['error' => 'unsupported_grant_type', 'error_description' => 'Unsupported grant type']),
    };
    if ($payload === null || ($payload['provider'] ?? null) !== $provider)
      self::json(400, [
        'error' => 'invalid_grant',
        'error_description' => 'Invalid or expired authorization code.',
      ]);

    [$access, $refresh] = FakeOAuth::issueTokens($payload);
    $response = [
      'access_token' => $access,
      'token_type' => 'Bearer',
      'expires_in' => self::TOKEN_LIFETIME[$provider],
      'refresh_token' => $refresh,
      'scope' => self::SCOPES[$provider],
    ];
    if ($provider === 'deviantart')
      $response['status'] = 'success';
    self::json(200, $response);
  }

  private static function bearerIdentity(string $provider):?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : null;
    $payload = FakeOAuth::lookupToken('access', $token);
    if ($payload === null || ($payload['provider'] ?? null) !== $provider)
      return null;

    return $payload['identity'];
  }

  /** GET /test-oauth/deviantart/api/v1/oauth2/user/whoami */
  public function deviantartWhoami():void {
    $identity = self::bearerIdentity('deviantart');
    if ($identity === null)
      self::json(401, ['error' => 'invalid_token', 'error_description' => 'Expired oAuth2 user token. The client should request a new one with an access code or a refresh token.', 'status' => 'error']);

    self::json(200, [
      'userid' => $identity['userid'],
      'username' => $identity['username'],
      // An inline 1x1 GIF: the app forces avatar URLs to https://, which this plain-HTTP test server can't
      // serve, and tests must not reach out to deviantart.com either
      'usericon' => 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
      'type' => 'regular',
    ]);
  }

  /** GET /test-oauth/discord/api/users/@me */
  public function discordMe():void {
    $identity = self::bearerIdentity('discord');
    if ($identity === null)
      self::json(401, ['message' => '401: Unauthorized', 'code' => 0]);

    self::json(200, [
      'id' => $identity['id'],
      'username' => $identity['username'],
      'global_name' => $identity['global_name'],
      'discriminator' => '0',
      'avatar' => null,
    ]);
  }

  /** GET /test-oauth/discord/api/guilds/[guild_id]/members/[user_id] (called with the bot token) */
  public function discordGuildMember(array $params):void {
    $member = FakeOAuth::getGuildMember($params['user_id']);
    if ($member === null)
      self::json(404, ['message' => 'Unknown Member', 'code' => 10007]);

    self::json(200, $member);
  }
}
