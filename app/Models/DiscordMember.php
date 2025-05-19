<?php

namespace App\Models;

use ActiveRecord\DateTime;
use App\Controllers\DiscordAuthController;
use App\CoreUtils;
use App\Response;
use App\Time;
use App\UserPrefs;
use GuzzleHttp\Command\Exception\CommandClientException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use RestCord\DiscordClient;
use Wohali\OAuth2\Client\Provider\Discord;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;
use Wohali\OAuth2\Client\Provider\Exception\DiscordIdentityProviderException;

/**
 * @property string   $id
 * @property string   $name
 * @property int      $user_id
 * @property string   $username
 * @property string   $display_name
 * @property string   $nick
 * @property string   $avatar_hash
 * @property DateTime $joined_at
 * @property number   $discriminator
 * @property string   $access        (oAuth)
 * @property string   $refresh       (oAuth)
 * @property string   $scope         (oAuth)
 * @property DateTime $expires       (oAuth)
 * @property DateTime $last_synced
 * @property string   $discord_tag   (Via magic method)
 * @property string   $avatar_url    (Via magic method)
 * @property User     $user          (Via relations)
 * @method static DiscordMember|DiscordMember[] find(...$args)
 * @method static DiscordMember|null find_by_user_id(int $user_id)
 */
class DiscordMember extends NSModel {
  public static $belongs_to = [
    ['user'],
  ];

  public function get_name() {
    return !empty($this->nick) ? $this->nick : ($this->display_name ?? $this->username);
  }

  public function get_discord_tag() {
    return $this->discriminator  === 0 ? "@{$this->username}" : "{$this->username}#{$this->discriminator}";
  }

  public function get_avatar_url() {
    if (empty($this->avatar_hash)) {
      $file_name = $this->discriminator === 0 ? ($this->id >> 22) : $this->discriminator;
      return "https://cdn.discordapp.com/embed/avatars/$file_name.png";
    }

    $ext = str_starts_with($this->avatar_hash, "a_") ? 'gif' : 'png';

    return "https://cdn.discordapp.com/avatars/{$this->id}/{$this->avatar_hash}.$ext";
  }

  public function isServerMember(bool $recheck = false) {
    if ($recheck)
      $this->checkServerMembership();

    return $this->joined_at !== null;
  }

  public function isLinked() {
    return $this->access !== null;
  }

  public function checkServerMembership() {
    $discordApi = new DiscordClient(['token' => CoreUtils::env('DISCORD_BOT_TOKEN')]);
    try {
      $member = $discordApi->guild->getGuildMember([
        'guild.id' => (int)CoreUtils::env('DISCORD_SERVER_ID'),
        'user.id' => $this->id,
      ]);
    }
    catch (CommandClientException $e){
      if ($e->getResponse()?->getStatusCode() !== 404)
        throw $e;
    }
    if (!empty($member)){
      $this->nick = $member->nick ?? null;
      $this->joined_at = $member->joined_at->format('c');
    }
    else {
      $this->nick = null;
      $this->joined_at = null;
    }
    $this->save();
  }

  public static function getUserData(Discord $provider, AccessToken $token):?DiscordResourceOwner {
    try {
      /** @noinspection PhpIncompatibleReturnTypeInspection */
      return $provider->getResourceOwner($token);
    }
    catch (DiscordIdentityProviderException $e){
      if ($e->getCode() === 401){
        // We've been de-authorized
        return null;
      }
      throw $e;
    }
  }

  public function updateFromApi(DiscordResourceOwner $user, bool $save = true) {
    $this->username = $user->getUsername();
    $this->display_name = $user->toArray()['global_name'];
    $this->discriminator = $user->getDiscriminator();
    $this->avatar_hash = $user->getAvatarHash();
    if ($save)
      $this->save();
  }

  public function accessTokenExpired():bool {
    return $this->expires !== null && $this->expires->getTimestamp() <= time() + 10;
  }

  public function updateAccessToken(?AccessTokenInterface $token = null, bool $save = true):void {
    if ($token === null){
      if (!$this->accessTokenExpired())
        return;

      $provider = DiscordAuthController::getProvider();
      try {
        $token = $provider->getAccessToken('refresh_token', ['refresh_token' => $this->refresh]);
      }
      catch (DiscordIdentityProviderException $e){
        if ($e->getMessage() === '{"error":"invalid_grant"}'){
          $this->delete();
          Response::fail('The Discord account link got severed, you will need to re-link your account.', ['segway' => true]);
        }
        else throw $e;
      }
    }
    $this->access = $token->getToken();
    $this->refresh = $token->getRefreshToken();
    $this->expires = date('c', $token->getExpires());
    $this->scope = $token->getValues()['scope'];
    if ($save)
      $this->save();
  }

  public const SYNC_COOLDOWN = 5 * Time::IN_SECONDS['minute'];

  public function canBeSynced() {
    return ($this->last_synced === null || $this->last_synced->getTimestamp() + self::SYNC_COOLDOWN <= time()) && $this->isLinked();
  }

  public function sync(Discord $provider = null, bool $force = false, bool $auto_unlink = true):bool {
    if (!$force && !$this->canBeSynced())
      return true;

    if ($provider === null)
      $provider = DiscordAuthController::getProvider();
    $this->updateAccessToken(null, false);
    $user = self::getUserData($provider, new AccessToken(['access_token' => $this->access]));
    if ($user === null){
      if ($auto_unlink){
        $this->delete();
        Response::fail('The site is no longer authorized to access the Discord account data, the link has been removed.', ['segway' => true]);
      }
      else return false;
    }
    $this->updateFromApi($user);
    $this->last_synced = date('c');
    $this->checkServerMembership();

    return true;
  }
}
