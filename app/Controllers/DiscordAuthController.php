<?php

namespace App\Controllers;

use App\Auth;
use App\CoreUtils;
use App\DB;
use App\HTTP;
use App\JSON;
use App\Models\DiscordMember;
use App\Models\User;
use App\Permission;
use App\Response;
use App\Testing\FakeOAuth;
use App\Time;
use GuzzleHttp\Exception\RequestException;
use Wohali\OAuth2\Client\Provider\Discord;
use OpenApi\Annotations as OA;
use Wohali\OAuth2\Client\Provider\Exception\DiscordIdentityProviderException;

class DiscordAuthController extends Controller {
  /** @var Discord */
  private $provider;

  public function __construct() {
    if (isset($_POST['key'])){
      if (!hash_equals(CoreUtils::env('WS_SERVER_KEY'), $_POST['key']))
        CoreUtils::noPerm();
      $this->trusted = true;
    }
    else {
      parent::__construct();

      if (!Auth::$signed_in){
        if (CoreUtils::isJSONExpected()){
          Response::error(401);
        }
        CoreUtils::noPerm();
      }
    }

    $this->provider = self::getProvider();
  }

  private function getReturnUrl():string {
    return Auth::$user->toURL(true).'/account#discord-connect';
  }

  public static function getProvider():Discord {
    $options = [
      'clientId' => CoreUtils::env('DISCORD_CLIENT'),
      'clientSecret' => CoreUtils::env('DISCORD_SECRET'),
      'redirectUri' => ABSPATH.'discord-connect/end',
    ];
    // In TEST_MODE, talk to the fake provider (TestOAuthController) instead of discord.com
    if (CoreUtils::env('TEST_MODE')){
      $options['host'] = FakeOAuth::baseUrl('discord');
      $options['apiDomain'] = FakeOAuth::baseUrl('discord').'/api';
    }

    return new Discord($options);
  }

  private function redirectIfAlreadyLinked():void {
    if (Auth::$user->isDiscordLinked())
      HTTP::tempRedirect($this->getReturnUrl());
  }

  public function begin() {
    $this->redirectIfAlreadyLinked();

    $authUrl = $this->provider->getAuthorizationUrl([
      'scope' => ['identify', 'guilds'],
    ]);
    Auth::$session->setData('discord_state', $this->provider->getState());
    HTTP::tempRedirect($authUrl);
  }

  public function end() {
    $this->redirectIfAlreadyLinked();

    $returnUrl = $this->getReturnUrl();

    if (!isset($_GET['code'], $_GET['state']) || $_GET['state'] !== Auth::$session->pullData('discord_state'))
      HTTP::tempRedirect($returnUrl);

    try {
      $token = $this->provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
    }
    catch (DiscordIdentityProviderException $e){
      if (CoreUtils::contains($e->getMessage(), 'invalid_grant')){
        CoreUtils::logError('Discord connection resulted in invalid_grant error, redirecting to beginning');
        HTTP::tempRedirect('/discord-connect/begin');
      }
      throw $e;
    }
    $discord_user_res = DiscordMember::getUserData($this->provider, $token);
    if ($discord_user_res === null) {
      HTTP::tempRedirect($returnUrl);
    }

    $discord_id = (int) $discord_user_res->getId();

    $discord_user = DiscordMember::find($discord_id);
    if (empty($discord_user)){
      $discord_user = new DiscordMember();
      $discord_user->id = $discord_id;
    }

    // Delete any existing member records for this user that do not have this Discord user ID
    DB::$instance->where('user_id', Auth::$user->id)->where('id', $discord_id, '!=')->delete('discord_members');

    $discord_user->user_id = Auth::$user->id;
    $discord_user->last_synced = date('c');
    $discord_user->updateFromApi($discord_user_res);
    $discord_user->updateAccessToken($token);
    $discord_user->checkServerMembership();

    HTTP::tempRedirect($returnUrl);
  }

  private ?User $target;
  private bool $same_user;
  /** The websocket server authenticates with a key instead of a session and may act on any user */
  private bool $trusted = false;

  private function setTarget($params):void {
    $this->target = User::find($params['user_id']);
    if (false === $this->target instanceof User)
      CoreUtils::notFound();
    if (!$this->trusted && $this->target->id !== Auth::$user->id && Permission::insufficient('staff'))
      Response::denied();

    if (!$this->target->boundToDiscordMember())
      Response::error(409, 'You must be bound to a Discord user to perform this action');

    $this->same_user = !$this->trusted && $this->target->id === Auth::$user->id;
  }

  /**
   * @OA\Post(
   *   path="/users/{user_id}/discord/sync",
   *   description="Refresh the stored Discord account information (name, avatar, server membership) of a user. The user themselves or staff. At most once every 5 minutes.",
   *   tags={"discord"},
   *   @OA\Parameter(in="path", name="user_id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="204", description="Synced"),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Not the user or staff", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="409", description="No Discord account is bound to the user, or it is not linked", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="429", description="Synced less than 5 minutes ago", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function sync($params) {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    $this->setTarget($params);

    $discordUser = $this->target->discord_member;
    if ($discordUser->access === null)
      Response::error(409, 'The Discord account must be linked before syncing');

    if (!$discordUser->canBeSynced())
      Response::error(429, 'The account information was last updated '.Time::format($discordUser->last_synced->getTimestamp(), Time::FORMAT_READABLE).', please wait at least 5 minutes before syncing again.');

    $discordUser->sync($this->provider);
    Response::noContent();
  }

  /**
   * @OA\Delete(
   *   path="/users/{user_id}/discord",
   *   description="Revoke the site's access to a user's Discord account and forget the account. The user themselves or staff.",
   *   tags={"discord"},
   *   @OA\Parameter(in="path", name="user_id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="Unlinked", @OA\JsonContent(type="object", required={"message"}, @OA\Property(property="message", type="string"))),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Not the user or staff", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="409", description="No Discord account is bound to the user", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="502", description="Discord refused to revoke the access", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function unlink($params) {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    $this->setTarget($params);

    $discord_user = $this->target->discord_member;
    if ($discord_user->isLinked()){
      $status_code = null;
      try {
        $req = $this->provider->getRequest('POST', $this->provider->apiDomain.'/oauth2/token/revoke', [
          'body' => http_build_query([
            'token' => $discord_user->refresh,
            'token_type_hint' => 'refresh_token',
            'client_id' => CoreUtils::env('DISCORD_CLIENT'),
          ]),
          'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
          ],
        ]);
        $res = $this->provider->getResponse($req);
        $status_code = $res->getStatusCode();
      }
      catch (RequestException $e){
        $response = $e->getResponse();
        if ($response !== null && (string)$response->getBody() === '{"error": "invalid_client"}'){
          $status_code = 200;
        }
        else throw $e;
      }
      if ($status_code !== 200){
        // Revoke failed
        CoreUtils::logError("Revoking Discord access failed for {$this->target->name}, details:\n".JSON::encode([
            'statusCode' => $res->getStatusCode(),
            'body' => (string)$res->getBody(),
          ], JSON_PRETTY_PRINT));
        Response::error(502, 'Revoking access failed, please let us know so we can look into the issue.');
      }
    }

    // Revoke successful
    $discord_user->delete();

    $Your = $this->same_user ? 'Your' : 'This';
    Response::ok(['message' => "$Your Discord account was successfully unlinked.".($this->same_user
        ? ' If you want to verify it yourself, check your Authorized Apps in your settings.' : '')]);
  }
}
