<?php

namespace App\Controllers\API;

use App\Auth;
use App\Controllers\Traits\UserLoaderTrait;
use App\CoreUtils;
use App\DB;
use App\Input;
use App\Models\BlockedEmail;
use App\Models\EmailVerification;
use App\Models\Session;
use App\Models\User;
use App\Permission;
use App\Response;
use App\Twig;
use App\Users;
use OpenApi\Annotations as OA;
use Throwable;

class UserAPIController extends APIController {
  use UserLoaderTrait;

  /**
   * @OA\Delete(
   *   path="/user/session/{id}",
   *   description="Deletes one of the current user's login sessions, or any session if the current user is staff",
   *   tags={"users"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="Session successfully removed",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="The session does not belong to the current user and they are not staff",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="No session found with this ID, or missing ID",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function sessionApi($params):void {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    if (!isset($params['id']))
      Response::fail('Missing session ID');

    $session = Session::find($params['id']);
    if (empty($session))
      Response::fail('This session does not exist');
    if ($session->user_id !== Auth::$user->id && Permission::insufficient('staff'))
      Response::fail('You are not allowed to delete this session');

    $session->delete();

    Response::success('Session successfully removed');
  }

  /**
   * @OA\Put(
   *   path="/user/{id}/role",
   *   description="Changes the role of the specified user. Requires staff permission, the target user must be in the same or a lower-level group than the requester, and a user cannot change their own role.",
   *   tags={"users"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/x-www-form-urlencoded",
   *       @OA\Schema(
   *         required={"value"},
   *         @OA\Property(property="value", ref="#/components/schemas/UserRole", description="The new role to assign to the user")
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="Role updated successfully (or already in that role, in which case 'already_in' is true)",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           @OA\Property(property="already_in", type="boolean")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission, or attempting to change own role, or attempting to change a higher-level user's role",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="User not found",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function roleApi($params):void {
    if ($this->action !== 'PUT')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::fail();

    if (!isset($params['id']))
      Response::fail('Missing user ID');

    $target_user = User::find($params['id']);
    if (empty($target_user))
      Response::fail('User not found');

    if ($target_user->id === Auth::$user->id)
      Response::fail('You cannot modify your own group');
    if (Permission::insufficient($target_user->role))
      Response::fail('You can only modify the group of users who are in the same or a lower-level group than you');

    $new_role = (new Input('value', 'role', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The new group is not specified',
        Input::ERROR_INVALID => 'The specified group (@value) does not exist',
      ],
    ]))->out();
    if ($target_user->role === $new_role)
      Response::done(['already_in' => true]);

    $target_user->updateRole($new_role);

    Response::done();
  }

  /**
   * @OA\Post(
   *   path="/user/password",
   *   description="Sets a new password for the currently signed in user. Requires staff permission. If a password is already set, the current password must be provided for verification. On success, all existing sessions of the user are deleted.",
   *   tags={"users","authentication"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/x-www-form-urlencoded",
   *       @OA\Schema(
   *         required={"new_password"},
   *         @OA\Property(property="current_password", type="string", description="The user's current password, required if a password is already set"),
   *         @OA\Property(property="new_password", type="string", minLength=8, maxLength=300, description="The new password to set")
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="Password successfully changed",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="401",
   *     description="Not signed in, or current password is incorrect",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (staff required)",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
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

    $new_password = (new Input('new_password', 'string', [
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
      Response::dbError('Could not set the password due to a database error');
    }

    if (!DB::$instance->getConnection()->commit()){
      CoreUtils::logError("Failed to commit new password changes");
      Response::dbError('Could not set the password due to a database error');
    }

    Response::success('Your new password has been set successfully. As a security precaution your existing sessions have been deleted, so you will need to log in again.');
  }

  /**
   * @OA\Post(
   *   path="/user/{id}/email",
   *   description="Requests an e-mail address change (or resend of a pending verification e-mail) for the specified user. Requires staff permission. When changing the address of the requester's own account, the current password must be set and verified first.",
   *   tags={"users"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/x-www-form-urlencoded",
   *       @OA\Schema(
   *         @OA\Property(property="resend", type="boolean", description="If true, resends the existing pending verification e-mail instead of requesting a new address change"),
   *         @OA\Property(property="new_email", type="string", minLength=3, maxLength=128, description="The new e-mail address to verify, required unless 'resend' is true"),
   *         @OA\Property(property="current_password", type="string", description="The user's current password, required when changing their own e-mail address")
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="A confirmation e-mail has been sent",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="401",
   *     description="Not signed in",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (staff required)",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
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
      $new_email = (new Input('new_email', 'string', [
        Input::IS_OPTIONAL => false,
        Input::IN_RANGE => [3, 128],
        Input::CUSTOM_ERROR_MESSAGES => [
          Input::ERROR_MISSING => 'The new e-mail is required',
          Input::ERROR_INVALID => 'The new e-mail is invalid',
          Input::ERROR_RANGE => 'The new e-mail must be between @min and @max characters long',
        ],
      ]))->out();

      if ($new_email === $this->user->email){
        Response::fail('You are trying to use same e-mail address '.($same_user ? 'you' : 'this user').' already '.($same_user ? 'have' : 'has').' set');
      }

      Users::validateEmail($new_email);

      if ($same_user){
        if (!$this->user->getPasswordSet()){
          Response::fail('You will need to set a password first before changing your e-mail address');
        }

        Users::validateCurrentPassword($this->user);
      }

      $users_with_this_email_exist = User::exists(['conditions' => ['email' => $new_email]]);
      if ($users_with_this_email_exist){
        Response::fail('This e-mail address is already in use by another user');
      }
    }

    if (!Users::sendEmailValidation($this->user, $new_email)){
      Response::fail('There was an issue while trying to send a confirmation e-mail, please try again later');
    }

    Response::success('A confirmation e-mail has been sent to the specified address with a link to verify your address. Click the link to update the address in your account.');
  }

  /**
   * @OA\Post(
   *   path="/user/verify",
   *   description="Verifies or blocks an e-mail address based on a verification hash sent to that address. Requires staff permission.",
   *   tags={"users"},
   *   @OA\RequestBody(
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/x-www-form-urlencoded",
   *       @OA\Schema(
   *         required={"hash","action"},
   *         @OA\Property(property="hash", type="string", minLength=128, maxLength=128, description="The verification hash sent to the e-mail address"),
   *         @OA\Property(property="action", type="string", enum={"verify","block"}, description="Whether to verify the e-mail address for the account, or add it to the do-not-send list")
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="The e-mail address was successfully verified or blocked",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="400",
   *     description="The verification hash is invalid or expired, or the e-mail address could not be updated",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (staff required)",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
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
      Response::fail('The specified validation hash is either invalid or has expired');
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

      Response::success('Your e-mail address has been added to our do-not-send list successfully.');
    }

    if (!$verification->user->setVerifiedEmail($verification)) {
      Response::fail('Could not update the e-mail address in the database.');
    }

    Response::success('Your e-mail address has been verified successfully.');
  }

  /**
   * @OA\Delete(
   *   path="/user/{id}/contrib-cache",
   *   description="Clears the cached contributions list of the specified user and returns the freshly rendered contributions HTML. Requires staff permission.",
   *   tags={"users"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="Contributions cache successfully cleared",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"html"},
   *           @OA\Property(property="html", type="string", description="Rendered contributions section HTML")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission (staff required)",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist, or missing ID",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function contribCacheApi($params):void {
    if ($this->action !== 'DELETE')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::fail('You are not allowed to clear contribution caches');

    if (!isset($params['id']))
      Response::fail('Missing user ID');

    $user = User::find($params['id']);
    if (empty($user))
      Response::fail('The specified user does not exist');

    unlink($user->getCachedContributionsPath());

    $same_user = Auth::$signed_in && $user->id === Auth::$user->id;
    $contribs = $user->getCachedContributions();
    $contrib_cache_duration = Users::getContributionsCacheDuration();

    Response::success('Contributions cache successfully cleared', [
      'html' => Twig::$env->render('user/_profile_contributions.html.twig', [
        'user' => $user,
        'same_user' => $same_user,
        'contribs' => $contribs ?? null,
        'contrib_cache_duration' => $contrib_cache_duration ?? null,
        'wrap' => false,
      ]),
    ]);
  }

  /**
   * @OA\Get(
   *   path="/user/{id}/avatar-wrap",
   *   description="Returns rendered HTML for the avatar of the specified user",
   *   tags={"users"},
   *   security={},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           required={"html"},
   *           @OA\Property(property="html", type="string", description="Rendered avatar wrapper HTML")
   *         )
   *       }
   *     )
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function avatarWrap($params):void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $this->load_user($params);

    Response::done(['html' => $this->user->getAvatarWrap()]);
  }
}
