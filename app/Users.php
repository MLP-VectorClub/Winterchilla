<?php

namespace App;

use App\Exceptions\CURLRequestException;
use App\Models\BlockedEmail;
use App\Models\Cutiemark;
use App\Models\DeviantartUser;
use App\Models\EmailVerification;
use App\Models\Post;
use App\Models\PreviousUsername;
use App\Models\Session;
use App\Models\User;
use EmailValidator\Validator as EmailValidator;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Hashing\HashManager;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class Users {
  public const RESERVATION_LIMIT = 4;

  // Global cache for storing DA user details
  public static array $da_user_cache = [];
  public static array $preferences_cache = [];

  /**
   * User Information Retriever
   * --------------------------
   * Gets a single row from the 'users' database where $column is equal to $value
   * Returns null if user is not found and false if user data could not be fetched
   *
   * @param string $value
   * @param string $column
   *
   * @return DeviantartUser|null
   * @throws Exception
   */
  public static function getDA(string $value, string $column = 'id'):?DeviantartUser {
    if ($column === 'id')
      return DeviantartUser::find($value);

    if ($column === 'name' && !empty(self::$da_user_cache[$value]))
      return self::$da_user_cache[$value];

    $user = DB::$instance->where($column, $value)->getOne('deviantart_users');

    if (empty($user) && $column === 'name'){
      if (Regexes::$username->match($value)){
        $user = self::fetchDA($value);
      }
    }

    if (isset($user->name))
      self::$da_user_cache[$user->name] = $user;

    return $user;
  }

  /**
   * User Information Fetching
   * -------------------------
   * Fetch user info from DeviantArt's API
   * If multiple usernames are passed as an array it will not return a value
   *
   * @param string $username
   *
   * @return DeviantartUser|null
   * @throws Exception
   */
  public static function fetchDA(string $username):?DeviantartUser {
    $via_previous_name = PreviousUsername::find_by_username($username);
    if (!empty($via_previous_name)){
      return $via_previous_name->user;
    }

    if (Auth::$session->access === null)
      return null;

    $fetch_params = ["usernames[0]" => $username];

    try {
      $user_data = DeviantArt::request('user/whois', null, $fetch_params);
    }
    catch (CURLRequestException $e){
      return null;
    }

    if (empty($user_data['results'][0]))
      return null;

    $user_data = $user_data['results'][0];
    $save_success = false;
    $id = strtolower($user_data['userid']);

    $da_user = DeviantartUser::find($id);
    $user_exists = $da_user !== null;
    if (!$user_exists){
      /** @var User $user */
      $user = User::create([
        'name' => $user_data['username'],
      ]);

      $da_user = new DeviantartUser([
        'id' => $id,
        'created_at' => date('c'),
        'user_id' => $user->id,
      ]);
    }

    $da_user->name = $user_data['username'];
    $da_user->avatar_url = URL::makeHttps($user_data['usericon']);
    if ($da_user->save())
      $save_success = true;

    if (!$save_success)
      throw new RuntimeException('Saving user data failed'.(Permission::sufficient('developer') ? ': '.DB::$instance->getLastError() : ''));

    if ($user_exists){
      $previous_name = $da_user->user->name;
      foreach ([$previous_name, $username] as $i => $old_name){
        if (strcasecmp($old_name, $da_user->name) === 0)
          continue;

        PreviousUsername::record($da_user->id, $old_name);
      }
      if (strcasecmp($da_user->user->name, $da_user->name) !== 0){
        $da_user->user->update_attributes(['name' => $da_user->name]);
      }
    }

    return $da_user;
  }

  /**
   * Check maximum simultaneous reservation count
   *
   * @param bool $return_as_bool
   *
   * @return bool|null
   */
  public static function checkReservationLimitReached(bool $return_as_bool = false) {
    $resserved_count = DB::$instance
      ->where('reserved_by', Auth::$user->id)
      ->where('deviation_id IS NULL')
      ->count('posts');

    $overTheLimit = !empty($resserved_count) && $resserved_count >= self::RESERVATION_LIMIT;
    if ($return_as_bool)
      return $overTheLimit;
    if ($overTheLimit)
      Response::fail("You've already reserved {$resserved_count} images, and you can't have more than 4 pending reservations at a time. You can review your reservations on your <a href='/user'>Account page</a>, finish at least one of them before trying to reserve another image.");
  }

  /**
   * Check authentication cookie and set Auth class static properties
   *
   * @throws InvalidArgumentException
   */
  public static function authenticate() {
    if (Auth::$signed_in)
      return;

    if (!Cookie::exists('access')){
      Auth::$session = Session::newGuestSession();

      return;
    }
    if (Cookie::exists('access')){
      Auth::$session = Session::find_by_token(CoreUtils::sha256(Cookie::get('access')));
      if (Auth::$session !== null)
        Auth::$user = Auth::$session->user;
      else Auth::$session = Session::newGuestSession();

      if (Auth::$user === null)
        Auth::$session->last_visit = date('c');
    }

    if (!empty(Auth::$user->id)){
      // TODO When re-implementing banning, this could be re-used
      /* if ($ban_condition)
        Session::table()->delete(['user_id' => Auth::$user->id]);
      else { */
      Auth::$signed_in = true;
      Auth::$session->registerVisit();
      if (Auth::$session->expired)
        Auth::$session->refreshAccessToken();
      //}
    }
    else if (Auth::$session === null)
      Cookie::delete('access', Cookie::HTTP_ONLY);
  }

  public const PROFILE_SECTION_PRIVACY_LEVEL = [
    'developer' => "<span class='typcn typcn-cog color-red' title='Visible to: developer'></span>",
    'public' => "<span class='typcn typcn-world color-blue' title='Visible to: public'></span>",
    'staff' => "<span class='typcn typcn-lock-closed' title='Visible to: you & group staff'></span>",
    'staffOnly' => "<span class='typcn typcn-lock-closed color-red' title='Visible to: group staff'></span>",
    'private' => "<span class='typcn typcn-lock-closed color-green' title='Visible to: you'></span>",
  ];

  public static function calculatePersonalCGNextSlot(int $postcount):int {
    return 10 - ($postcount % 10);
  }

  public static function validateName($key, $errors = null, $method_get = false, $silent_fail = false):?string {
    return (new Input($key, 'username', [
      Input::IS_OPTIONAL => true,
      Input::SOURCE => $method_get ? 'GET' : 'POST',
      Input::SILENT_FAILURE => $silent_fail,
      Input::NO_LOGGING => $silent_fail,
      Input::CUSTOM_ERROR_MESSAGES => $errors ?? [
          Input::ERROR_MISSING => 'Username (@value) is missing',
          Input::ERROR_INVALID => 'Username (@value) is invalid',
        ],
    ]))->out();
  }

  public static function getContributionsCacheDuration(string $unit = 'hour'):string {
    $cache_dur = User::CONTRIB_CACHE_DURATION / Time::IN_SECONDS[$unit];

    return CoreUtils::makePlural($unit, $cache_dur, PREPEND_NUMBER);
  }

  //const NOPE = '<em>Nope</em>';
  public const NOPE = '<span class="typcn typcn-times"></span>';

  private static function _contribItemFinished(Post $item):string {
    if ($item->deviation_id === null)
      return self::NOPE;
    $HTML = "<div class='deviation-promise image-promise' data-favme='{$item->deviation_id}'></div>";
    if ($item->finished_at !== null){
      $finished_at = Time::tag($item->finished_at);
      $HTML .= "<div class='finished-at-ts'><span class='typcn typcn-time'></span> $finished_at</div>";
    }

    return $HTML;
  }

  private static function _contribItemApproved(Post $item):string {
    if (empty($item->lock))
      return self::NOPE;

    $HTML = '<span class="color-green typcn typcn-tick"></span>';
    $approval_entry = $item->approval_entry;
    if ($approval_entry !== null){
      if (Permission::sufficient('staff')){
        $approved_by = $approval_entry->user->toAnchor();
        $HTML .= "<div class='approved-by'><span class='typcn typcn-user'></span> $approved_by</div>";
      }
      $approved_at = Time::tag($approval_entry->created_at);
      $HTML .= "<div class='approved-at-ts'><span class='typcn typcn-time'></span> $approved_at</div>";
    }

    return $HTML;
  }

  public static function getContributionListHTML(string $type, ?array $data, bool $wrap = WRAP):string {
    switch ($type){
      case 'cms-provided':
        $TABLE = <<<HTML
					<th>Appearance</th>
					<th>Deviation</th>
					HTML;
      break;
      case 'requests':
        $TABLE = <<<HTML
					<th>Post</th>
					<th>Posted <span class="typcn typcn-arrow-sorted-down" title="Newest first"></span></th>
					<th>Reserved?</th>
					<th>Finished?</th>
					<th>Approved?</th>
					HTML;
      break;
      case 'reservations':
        $TABLE = <<<HTML
					<th>Post</th>
					<th>Posted <span class="typcn typcn-arrow-sorted-down" title="Newest first"></span></th>
					<th>Finished?</th>
					<th>Approved?</th>
					HTML;
      break;
      case 'finished-posts':
        $TABLE = <<<HTML
					<th>Post</th>
					<th>Posted <span class="typcn typcn-arrow-sorted-down" title="Newest first"></span></th>
					<th>Reserved</th>
					<th>Deviation</th>
					<th>Approved?</th>
					HTML;
      break;
      case 'fulfilled-requests':
        $TABLE = <<<HTML
					<th>Post</th>
					<th>Posted</th>
					<th>Finished <span class="typcn typcn-arrow-sorted-down" title="Newest first"></span></th>
					<th>Deviation</th>
					HTML;
      break;
      default:
        throw new Exception(__METHOD__.": Missing table heading definitions for type $type");
    }
    $TABLE = "<thead><tr>$TABLE</tr></thead>";

    foreach ($data as $item){
      switch ($type){
        case 'cms-provided':
          /** @var $item Cutiemark */
          $appearance = $item->appearance;
          $preview = $appearance->toAnchorWithPreview();
          $deviation = $item->favme !== null ? "<div class='deviation-promise image-promise' data-favme='{$item->favme}'></div>" : self::NOPE;

          $TR = <<<HTML
						<td class="pony-link">$preview</td>
						<td>$deviation</td>
						HTML;

        break;
        case 'requests':
          /** @var $item Post */
          $preview = $item->toAnchorWithPreview();
          $posted = Time::tag($item->requested_at);
          if ($item->reserved_by !== null){
            $reserved_by = $item->reserver->toAnchor();
            $reserved_at = Time::tag($item->reserved_at);
            $reserved = "<span class='typcn typcn-user' title='By'></span> $reserved_by<br><span class='typcn typcn-time'></span> $reserved_at";
          }
          else $reserved = self::NOPE;
          $finished = self::_contribItemFinished($item);
          $approved = self::_contribItemApproved($item);
          $TR = <<<HTML
						<td>$preview</td>
						<td>$posted</td>
						<td class="by-at">$reserved</td>
						<td>$finished</td>
						<td class="approved">$approved</td>
						HTML;
        break;
        case 'reservations':
          /** @var $item Post */
          $preview = $item->toAnchorWithPreview();
          $posted = Time::tag($item->reserved_at);
          $finished = self::_contribItemFinished($item);
          $approved = self::_contribItemApproved($item);
          $TR = <<<HTML
						<td>$preview</td>
						<td>$posted</td>
						<td>$finished</td>
						<td class="approved">$approved</td>
						HTML;
        break;
        case 'finished-posts':
          /** @var $item Post */
          $preview = $item->toAnchorWithPreview();
          $posted_by = ($item->is_request ? $item->requester : $item->reserver)->toAnchor();
          $posted_at = Time::tag($item->posted_at);
          $posted = "<span class='typcn typcn-user' title='By'></span> $posted_by<br><span class='typcn typcn-time'></span> $posted_at";
          if ($item->is_request){
            $posted = "<td class='by-at'>$posted</td>";
            $reserved = '<td>'.Time::tag($item->reserved_at).'</td>';
          }
          else {
            $posted = "<td colspan='2'>$posted</td>";
            $reserved = '';
          }
          $finished = self::_contribItemFinished($item);
          $approved = self::_contribItemApproved($item);
          $TR = <<<HTML
						<td>$preview</td>
						$posted
						$reserved
						<td>$finished</td>
						<td class="approved">$approved</td>
						HTML;
        break;
        case 'fulfilled-requests':
          /** @var $item Post */
          $preview = $item->toAnchorWithPreview();
          $posted_by = $item->requester->toAnchor();
          $requested_at = Time::tag($item->requested_at);
          $posted = "<span class='typcn typcn-user' title='By'></span> $posted_by<br><span class='typcn typcn-time'></span> $requested_at";
          $finished = $item->finished_at === null ? '<span class="typcn typcn-time missing-time" title="Time data missing"></span>'
            : Time::tag($item->finished_at);
          $deviation = "<div class='deviation-promise image-promise' data-favme='{$item->deviation_id}'></div>";
          $TR = <<<HTML
						<td>$preview</td>
						<td class='by-at'>$posted</td>
						<td>$finished</td>
						<td>$deviation</td>
						HTML;
        break;
        default:
          $TR = '';
      }

      $TABLE .= "<tr>$TR</tr>";
    }

    return $wrap ? "<table id='contribs'>$TABLE</table>" : $TABLE;
  }

  /**
   * Conditionally map users to either legacy or new user records
   * based on the type of the ID
   * TODO Remove if log data has been migrated
   *
   * @param string|int $user_id Legacy uuid or integer
   *
   * @return User|null
   */
  public static function resolveById($user_id):?User {
    if (is_int($user_id)) return User::find($user_id);

    $da_user = DeviantartUser::find($user_id);

    return $da_user === null ? null : $da_user->user;
  }

  public static function getHashManager():HashManager {
    $container = new Container();
    $container->singleton('config', fn() => new class {
      function get(string $what):?string {
        switch ($what){
          case 'hashing.driver':
            return 'bcrypt';
          case 'hashing.bcrypt':
            return null;
          default:
            throw new RuntimeException("Unhandled config value $what");
        }
      }
    });

    return new HashManager($container);
  }

  public static function validateCurrentPassword(User $user, HashManager $hash_manager = null) {
    $current_password = (new Input('current_password', 'string', [
      Input::IS_OPTIONAL => false,
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The current password is required',
        Input::ERROR_INVALID => 'The current password is invalid',
      ],
    ]))->out();

    if ($hash_manager === null){
      $hash_manager = self::getHashManager();
    }

    if (!$hash_manager->check($current_password, $user->password)){
      /** @noinspection RandomApiMigrationInspection */
      usleep(rand(1, 3) * 1e6);
      Response::fail('The provided current password is incorrect');
    }
  }

  public static function sendEmailValidation(User $user, string $email = null): bool {
    $recipient = $email ?? $user->email;

    $block_entry = BlockedEmail::find_by_email($recipient);
    if ($block_entry) {
      Response::fail('The specified email address has been added to our do-not-send list. If you are the owner of this address and would like to be removed from this list please <a class="send-feedback">contact us</a>.');
    }

    $previous_validation_attempt = EmailVerification::find('first', [
      'conditions' => [
        "email = ? and now() - created_at <= INTERVAL '10 MINUTES'",
        $recipient,
      ],
      'order' => 'created_at desc'
    ]);
    if ($previous_validation_attempt !== null) {
      Response::fail('A confirmation email was sent to this address recently, please wait a bit before requesting another one');
    }

    try {
      $hash = bin2hex(random_bytes(64));
    }
    catch (Exception $e){
      CoreUtils::logError("Failed to get random_bytes for email verification link: {$e->getMessage()}\nStack trace:\n{$e->getTraceAsString()}");
      Response::fail('Could not generate a secure verification link, please try again later');
    }

    $verification = EmailVerification::create([
      'user_id' => $user->id,
      'email' => $recipient,
      'hash' => $hash,
    ]);

    $result = false;
    try {
      $result = $verification->send();
    } catch (Throwable $e) {
      $verification->delete();
      CoreUtils::logError("Failed to send verification email: {$e->getMessage()}\nStack trace:\n{$e->getTraceAsString()}");
    }

    return $result;
  }

  public static function validateEmail(string $email):void {
    CoreUtils::checkStringValidity($email, 'new e-mail');

    if(!(new EmailValidator())->isValid($email)) {
      Response::fail('The provided e-mail address does not pass our validity checks, please use an e-mail address which is properly set up to receive messages.');
    }
  }
}
