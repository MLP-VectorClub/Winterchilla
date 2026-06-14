<?php

namespace App\Controllers;

use App\Auth;
use App\CGUtils;
use App\CoreUtils;
use App\DB;
use App\DeviantArt;
use App\File;
use App\HTTP;
use App\Input;
use App\Models\BlockedEmail;
use App\Models\DeviantartUser;
use App\Models\EmailVerification;
use App\Models\PreviousUsername;
use App\Models\Session;
use App\Models\User;
use App\Pagination;
use App\Permission;
use App\Response;
use App\Twig;
use App\UserPrefs;
use App\Users;
use OpenApi\Annotations as OA;
use RuntimeException;
use Throwable;
use function count;

/**
 * @OA\Schema(
 *   schema="Session",
 *   type="object",
 *   description="Represents a login session for a user",
 *   required={
 *     "id",
 *     "user_id",
 *     "platform",
 *     "browser_name",
 *     "browser_ver",
 *     "created",
 *     "last_visit",
 *     "expired"
 *   },
 *   additionalProperties=false,
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="user_id", type="integer"),
 *   @OA\Property(property="platform", type="string", nullable=true),
 *   @OA\Property(property="browser_name", type="string", nullable=true),
 *   @OA\Property(property="browser_ver", type="string", nullable=true),
 *   @OA\Property(property="created", type="string", format="date-time"),
 *   @OA\Property(property="last_visit", type="string", format="date-time"),
 *   @OA\Property(property="expired", type="boolean")
 * )
 */
class UserController extends Controller {
  use UserLoaderTrait;

  public function homepage():void {
    if (UserPrefs::get('p_homelastep'))
      HTTP::tempRedirect('/episode/latest');

    CGUtils::redirectToPreferredGuidePath();
  }

  public function profile($params):void {
    $user_id = $params['user_id'] ?? null;

    $error = null;
    $sub_error = null;
    if ($user_id === null){
      if (Auth::$signed_in)
        $user = Auth::$user;
      else {
        $error = 'Settings';
        $sub_error = 'You must sign in to view your settings';
      }
    }
    else $user = User::find($user_id);

    if (empty($user) || !($user instanceof User)){
      if (!isset($error)){
        $error = 'User does not exist';
        $sub_error = 'Check the name for typos and try again';
      }
      $can_edit = $same_user = $dev_on_dev = false;
    }
    else {
      $pagePath = $user->toURL(false);
      CoreUtils::fixPath($pagePath);
      $same_user = Auth::$signed_in && $user->id === Auth::$user->id;
      $can_edit = !$same_user && Permission::sufficient('staff') && Permission::sufficient($user->role);
      $dev_on_dev = Permission::sufficient('developer') && Permission::sufficient('developer', $user->role);
    }

    if ($error !== null)
      HTTP::statusCode(404);
    else {
      $is_staff = Permission::sufficient('staff');

      if ($same_user || $is_staff){
        if (count($user->deviantart_user->previous_names) > 0){
          $old_names = implode(', ', array_map(fn(PreviousUsername $p) => $p->username, $user->deviantart_user->previous_names));
        }
      }

      $discord_membership = $user->safelyGetDiscordMember();

      $contribs = $user->getCachedContributions();
      $contrib_cache_duration = Users::getContributionsCacheDuration();

      if ($can_edit){
        $export_roles = [];
        $roles_copy = Permission::ROLES_ASSOC;
        unset($roles_copy['guest']);
        foreach ($roles_copy as $name => $label){
          if (Permission::insufficient($name, Auth::$user->role))
            continue;
          $export_roles[$name] = $label;
        }
      }
      else if ($dev_on_dev)
        $export_roles = Permission::ROLES_ASSOC;

      $pcg_section_is_private = UserPrefs::get('p_hidepcg', $user);
      $list_pcgs = !$pcg_section_is_private || $same_user || $is_staff;
      if ($list_pcgs)
        $personal_color_guides = $user->pcg_appearances;

      $awaiting_approval = $user->getPostsAwaitingApproval();
    }

    $settings = [
      'title' => $error === null ? ($same_user ? 'Your' : "{$user->name} -").' '.($same_user || $can_edit ? 'Account' : 'Profile')
        : 'Account',
      'noindex' => true,
      'css' => [true],
      'js' => [true],
      'og' => [
        'image' => !empty($user) ? $user->avatar_url : null,
        'description' => !empty($user) ? CoreUtils::posess($user->name)." profile on the MLP-VectorClub's website" : null,
      ],
      'import' => [
        'user' => $user ?? null,
        'discord_membership' => $discord_membership ?? null,
        'can_edit' => $can_edit,
        'same_user' => $same_user,
        'is_staff' => $is_staff ?? null,
        'dev_on_dev' => $dev_on_dev,
        'da_logo' => str_replace(' fill="#FFF"', '', File::get(APPATH.'img/da-logo.svg')),
        'old_names' => $old_names ?? null,
        'contribs' => $contribs ?? null,
        'contrib_cache_duration' => $contrib_cache_duration ?? null,
        'export_roles' => $export_roles ?? null,
        'section_is_private' => $pcg_section_is_private ?? null,
        'list_pcgs' => $list_pcgs ?? null,
        'personal_color_guides' => $personal_color_guides ?? null,
        'awaiting_approval' => $awaiting_approval ?? null,
      ],
    ];
    if ($error !== null)
      $settings['import']['error'] = $error;
    if ($sub_error !== null)
      $settings['import']['sub_error'] = $sub_error;
    if ($can_edit || $dev_on_dev)
      $settings['js'][] = 'pages/user/manage';
    $show_suggestions = $same_user;
    if ($show_suggestions){
      $settings['js'][] = 'pages/user/suggestion';
      $settings['css'][] = 'pages/user/suggestion';
    }
    $settings['import']['showSuggestions'] = $show_suggestions;
    CoreUtils::loadPage(__METHOD__, $settings);
  }

  public function profileByUuid($params):void {
    if (!isset($params['uuid']) || Permission::insufficient('developer'))
      CoreUtils::notFound();

    $da_user = DeviantartUser::find($params['uuid']);
    if (empty($da_user))
      CoreUtils::notFound();

    HTTP::permRedirect($da_user->user->toURL(false));
  }

  public function account($params):void {
    if (!isset($params['id'])){
      if (Auth::$signed_in){
        $params['id'] = Auth::$user->id;
      }
      else {
        CoreUtils::noPerm();
      }
    }

    $this->load_user($params);
    $same_user = Auth::$signed_in && $this->user->id === Auth::$user->id;
    if (!$same_user && !Permission::sufficient('staff')){
      CoreUtils::noPerm();
    }

    CoreUtils::fixPath($this->user->getAccountPagePath());

    $whose = $same_user ? 'Your' : CoreUtils::posess($this->user->name);
    $sessions = $this->user->sessions;

    CoreUtils::loadPage(__METHOD__, [
      'title' => "$whose Account",
      'heading' => 'Account Settings',
      'css' => [true],
      'js' => [true],
      'import' => [
        'same_user' => $same_user,
        'user' => $this->user,
        'sessions' => $sessions ?? null,
        'discord_membership' => $this->user->safelyGetDiscordMember(),
      ],
    ]);
  }

  public function verify() {
    $hash = isset($_GET['hash']) && preg_match('/^[a-f\d]+$/i', $_GET['hash']) ? $_GET['hash'] : null;
    $action = isset($_GET['action']) && $_GET['action'] === 'block' ? 'block' : 'verify';

    $heading = CoreUtils::capitalize($action).' E-mail Address';
    CoreUtils::loadPage(__METHOD__, [
      'title' => $heading,
      'heading' => $heading,
      'noindex' => true,
      'css' => [true],
      'js' => [true],
      'import' => [
        'hash' => $hash,
        'action' => $action,
      ],
    ]);
  }

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

  public const CONTRIB_NAMES = [
    'cms-provided' => 'Cutie Mark vectors provided',
    'requests' => 'Requests posted',
    'reservations' => 'Reservations posted',
    'finished-posts' => 'Posts finished',
    'fulfilled-requests' => 'Requests fulfilled',
  ];

  public function contrib($params):void {
    if (!isset(self::CONTRIB_NAMES[$params['type']]))
      CoreUtils::notFound();

    $user = User::find($params['user_id']);
    if (empty($user))
      CoreUtils::notFound();
    if ($user->id !== (Auth::$user->id ?? null) && $params['type'] === 'requests' && Permission::insufficient('staff'))
      CoreUtils::notFound();

    $items_per_page = 10;
    $pagination = new Pagination("{$user->toURL()}/contrib/{$params['type']}", $items_per_page);

    /** @var $cnt int */
    /** @var $data array */
    switch ($params['type']){
      case 'cms-provided':
        $cnt = $user->getCMContributions();
        $pagination->calcMaxPages($cnt);
        $data = $user->getCMContributions(false, $pagination);
      break;
      case 'requests':
        $cnt = $user->getRequestContributions();
        $pagination->calcMaxPages($cnt);
        $data = $user->getRequestContributions(false, $pagination);
      break;
      case 'reservations':
        $cnt = $user->getReservationContributions();
        $pagination->calcMaxPages($cnt);
        $data = $user->getReservationContributions(false, $pagination);
      break;
      case 'finished-posts':
        $cnt = $user->getFinishedPostContributions();
        $pagination->calcMaxPages($cnt);
        $data = $user->getFinishedPostContributions(false, $pagination);
      break;
      case 'fulfilled-requests':
        $cnt = $user->getApprovedFinishedRequestContributions();
        $pagination->calcMaxPages($cnt);
        $data = $user->getApprovedFinishedRequestContributions(false, $pagination);
      break;
      default:
        throw new RuntimeException(__METHOD__.": Missing data retriever for type {$params['type']}");
    }

    CoreUtils::fixPath($pagination->toURI());

    $title = "Page {$pagination->getPage()} - ".self::CONTRIB_NAMES[$params['type']].' - '.CoreUtils::posess($user->name).' Contributions';
    $heading = self::CONTRIB_NAMES[$params['type']].' by '.$user->toAnchor();
    CoreUtils::loadPage(__METHOD__, [
      'title' => $title,
      'heading' => $heading,
      'css' => [true],
      'js' => ['paginate', true],
      'import' => [
        'pagination' => $pagination,
        'user' => $user,
        'contrib_name' => self::CONTRIB_NAMES[$params['type']],
        'contribution_list' => Users::getContributionListHTML($params['type'], $data),
      ],
    ]);
  }

  public function contribLazyload($params):void {
    $CachedDeviation = DeviantArt::getCachedDeviation($params['favme']);
    if (empty($CachedDeviation))
      HTTP::statusCode(404, AND_DIE);

    Response::done(['html' => $CachedDeviation->toLinkWithPreview()]);
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

  public function list():void {
    $is_staff = Permission::sufficient('staff');
    if (!$is_staff){
      $can_see_users_with_roles = [];
      foreach (Permission::ROLES as $role => $level){
        if ($level >= Permission::ROLES['member'])
          $can_see_users_with_roles[] = $role;
      }
      DB::$instance->where('role', $can_see_users_with_roles);
    }

    /** @var $users User[] */
    $users = DB::$instance->orderBy('name')->get(User::$table_name);
    if (!empty($users)){
      $arranged = [];
      foreach ($users as $u){
        if (!isset($arranged[$u->role])) $arranged[$u->role] = [];

        $arranged[$u->maskedRole()][] = $u;
      }

      $sections = [];
      foreach (array_reverse(Permission::ROLES) as $r => $v){
        if (empty($arranged[$r])) continue;
        $users = $arranged[$r];
        $user_count = count($users);
        $group = CoreUtils::makePlural(Permission::ROLES_ASSOC[$r], $user_count, true);
        $staff_section = Permission::sufficient($r, 'staff');

        if ($user_count > 10){
          $users_out = [];
          foreach ($users as $u){
            $first_letter = strtoupper($u->name[0]);
            if (preg_match('/^[^a-z]$/i', $first_letter))
              $first_letter = '#';
            $users_out[$first_letter][] = $u->toAnchor();
          }

          ksort($users_out);

          $users_str = '';
          foreach ($users_out as $chr => $users){
            $users_str .= "<span class='letter-group'><strong>$chr</strong>".implode('', $users).'</span>';
          }
        }
        else {
          $users_str = '';
          if ($staff_section){
            foreach ($users as $user)
              $users_str .= sprintf("<div class='staff-block'>%s</div>", $user->toAnchor(WITH_AVATAR));
          }
          else $users_str = implode(', ', array_map(fn($u) => $u->toAnchor(), $users));
        }

        $sections[] = [
          $group,
          $users_str,
        ];
      }
    }

    CoreUtils::loadPage(__METHOD__, [
      'title' => $is_staff ? 'Users' : 'Club Members',
      'css' => [true],
      'import' => [
        'sections' => $sections ?? null,
      ],
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

  public function forceRedirect($params):void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (empty($params['name']))
      CoreUtils::notFound();

    $da_user = DeviantartUser::find_by_name($params['name']);

    if (empty($da_user))
      $da_user = Users::fetchDA($params['name']);

    if (empty($da_user))
      CoreUtils::notFound();

    $request_uri = $_SERVER['REQUEST_URI'];
    $new_uri = preg_replace('~^/(@|u/)'.USERNAME_CHARACTERS_PATTERN.'+~', "/users/{$da_user->user_id}", $request_uri);
    HTTP::tempRedirect($new_uri);
  }
}
