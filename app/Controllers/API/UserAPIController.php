<?php

namespace App\Controllers\API;

use App\Auth;
use App\Controllers\Traits\UserLoaderTrait;
use App\CoreUtils;
use App\DB;
use App\DeviantArt;
use App\Input;
use App\Models\BlockedEmail;
use App\Models\EmailVerification;
use App\Models\Session;
use App\Models\User;
use App\Permission;
use App\Response;
use App\Twig;
use App\Users;
use Throwable;

class UserAPIController extends APIController {
  use UserLoaderTrait;

  public function sessionApi($params):void {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    if (!Auth::$signed_in)
      Response::error(401);

    if (!isset($params['id']))
      Response::error(404, 'Missing session ID');

    $session = Session::find($params['id']);
    if (empty($session))
      Response::error(404, 'This session does not exist');
    if ($session->user_id !== Auth::$user->id && Permission::insufficient('staff'))
      Response::error(403, 'You are not allowed to delete this session');

    $session->delete();

    Response::noContent();
  }

  public function roleApi($params):void {
    if ($this->action !== 'PUT')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::denied();

    if (!isset($params['id']))
      Response::error(404, 'Missing user ID');

    $target_user = User::find($params['id']);
    if (empty($target_user))
      Response::error(404, 'User not found');

    if ($target_user->id === Auth::$user->id)
      Response::error(403, 'You cannot modify your own group');
    if (Permission::insufficient($target_user->role))
      Response::error(403, 'You can only modify the group of users who are in the same or a lower-level group than you');

    $new_role = (new Input('value', 'role', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The new group is not specified',
        Input::ERROR_INVALID => 'The specified group (@value) does not exist',
      ],
    ]))->out();
    if ($target_user->role === $new_role)
      Response::ok(['alreadyIn' => true]);

    $target_user->updateRole($new_role);

    Response::noContent();
  }

  public function passwordApi():void {
    if ($this->action !== 'POST'){
      CoreUtils::notAllowed();
    }

    if (!Auth::$signed_in){
      CoreUtils::noPerm();
    }

    CoreUtils::roleGate('staff');

    $hash_manager = Users::getHashManager();

    $password_set = Auth::$user->getPasswordSet();
    if ($password_set){
      Users::validateCurrentPassword(Auth::$user, $hash_manager);
    }

    $new_password = (new Input('newPassword', 'string', [
      Input::IS_OPTIONAL => false,
      Input::IN_RANGE => [8, 300],
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The new password is required',
        Input::ERROR_INVALID => 'The new password is invalid',
        Input::ERROR_RANGE => 'The new password must be between @min and @max characters long',
      ],
    ]))->out();

    // Disabled due to square1/pwned-check not being installable under PHP 8.1
    /* $compromised = false;
    try {
      $pwned = new Pwned([
        'connection_timeout' => 5,
        'remote_processing_timeout' => 2,
      ]);
      $compromised = $pwned->hasBeenPwned($new_password);
    }
    catch (ConnectionFailedException $e){
      CoreUtils::logError("Failed to check password compromised status: {$e->getMessage()}\nStack trace:{$e->getTraceAsString()}", Logger::WARNING);
    }
    if ($compromised){
      Response::fail('The specified new password is a known compromised password present in at least one data breach of other websites. Please chose a more unique password.');
    } */

    DB::$instance->getConnection()->beginTransaction();

    try {
      Auth::$user->password = $hash_manager->make($new_password);
      Auth::$user->save();

      Session::delete_all(['conditions' => ['user_id' => Auth::$user->id]]);
    }
    catch (Throwable $e){
      DB::$instance->getConnection()->rollBack();
      CoreUtils::logError("Failed to save new password: {$e->getMessage()}\nStack trace:\n{$e->getTraceAsString()}");
      Response::dbError('Could not set the password due to a database error', status: 500);
    }

    if (!DB::$instance->getConnection()->commit()){
      CoreUtils::logError("Failed to commit new password changes");
      Response::dbError('Could not set the password due to a database error', status: 500);
    }

    Response::ok(['message' => 'Your new password has been set successfully. As a security precaution your existing sessions have been deleted, so you will need to log in again.']);
  }

  public function emailApi($params):void {
    if ($this->action !== 'POST'){
      CoreUtils::notAllowed();
    }

    if (!Auth::$signed_in){
      CoreUtils::noPerm();
    }

    CoreUtils::roleGate('staff');

    $this->load_user($params);

    $same_user = Auth::$user->id === $this->user->id;
    if (!$same_user && !Permission::sufficient('staff')){
      CoreUtils::noPerm();
    }

    $resend = (new Input('resend', 'bool', [
      Input::IS_OPTIONAL => true,
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_INVALID => 'The resend value is invalid',
      ],
    ]))->out() ?? false;

    $new_email = null;
    if (!$resend){
      $new_email = (new Input('newEmail', 'string', [
        Input::IS_OPTIONAL => false,
        Input::IN_RANGE => [3, 128],
        Input::CUSTOM_ERROR_MESSAGES => [
          Input::ERROR_MISSING => 'The new e-mail is required',
          Input::ERROR_INVALID => 'The new e-mail is invalid',
          Input::ERROR_RANGE => 'The new e-mail must be between @min and @max characters long',
        ],
      ]))->out();

      if ($new_email === $this->user->email){
        Response::invalid('newEmail', 'You are trying to use same e-mail address '.($same_user ? 'you' : 'this user').' already '.($same_user ? 'have' : 'has').' set');
      }

      Users::validateEmail($new_email);

      if ($same_user){
        if (!$this->user->getPasswordSet()){
          Response::error(409, 'You will need to set a password first before changing your e-mail address');
        }

        Users::validateCurrentPassword($this->user);
      }

      $users_with_this_email_exist = User::exists(['conditions' => ['email' => $new_email]]);
      if ($users_with_this_email_exist){
        Response::invalid('newEmail', 'This e-mail address is already in use by another user');
      }
    }

    if (!Users::sendEmailValidation($this->user, $new_email)){
      Response::error(503, 'There was an issue while trying to send a confirmation e-mail, please try again later');
    }

    Response::ok(['message' => 'A confirmation e-mail has been sent to the specified address with a link to verify your address. Click the link to update the address in your account.']);
  }

  public function verifyApi():void {
    if ($this->action !== 'POST'){
      CoreUtils::notAllowed();
    }

    CoreUtils::roleGate('staff');

    $hash = (new Input('hash', 'string', [
      Input::IS_OPTIONAL => false,
      Input::IN_RANGE => [128, 128],
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The hash value is missing',
        Input::ERROR_INVALID => 'The hash value is invalid',
        Input::ERROR_RANGE => 'The hash value must be exactly @min characters long',
      ],
    ]))->out();

    $verification = EmailVerification::find_by_hash($hash);
    if ($verification === null || !$verification->isValid()) {
      Response::invalid('hash', 'The specified validation hash is either invalid or has expired');
    }

    $action = (new Input('action', 'string', [
      Input::IS_OPTIONAL => false,
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The action value is missing',
        Input::ERROR_INVALID => 'The action value is invalid',
      ],
    ]))->out();

    if ($action === 'block') {
      BlockedEmail::record($verification->email);

      Response::ok(['message' => 'Your e-mail address has been added to our do-not-send list successfully.']);
    }

    if (!$verification->user->setVerifiedEmail($verification)) {
      Response::error(500, 'Could not update the e-mail address in the database.');
    }

    Response::ok(['message' => 'Your e-mail address has been verified successfully.']);
  }

  public function contribCacheApi($params):void {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::denied('You are not allowed to clear contribution caches');

    if (!isset($params['id']))
      Response::error(404, 'Missing user ID');

    $user = User::find($params['id']);
    if (empty($user))
      Response::error(404, 'The specified user does not exist');

    $cache_path = $user->getCachedContributionsPath();
    if (file_exists($cache_path))
      unlink($cache_path);

    $same_user = Auth::$signed_in && $user->id === Auth::$user->id;
    $contribs = $user->getCachedContributions();
    $contrib_cache_duration = Users::getContributionsCacheDuration();

    Response::ok([
      'message' => 'Contributions cache successfully cleared',
      'html' => Twig::$env->render('user/_profile_contributions.html.twig', [
        'user' => $user,
        'same_user' => $same_user,
        'contribs' => $contribs ?? null,
        'contrib_cache_duration' => $contrib_cache_duration ?? null,
        'wrap' => false,
      ]),
    ]);
  }

  public function avatarWrap($params):void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_user($params);

    Response::ok(['html' => $this->user->getAvatarWrap()]);
  }

  public function contribLazyload($params):void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $cached_deviation = DeviantArt::getCachedDeviation($params['favme']);
    if (empty($cached_deviation))
      Response::error(404, 'The deviation could not be found');

    Response::ok(['html' => $cached_deviation->toLinkWithPreview()]);
  }
}
