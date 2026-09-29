<?php

namespace App\Controllers;

use App\Auth;
use App\CGUtils;
use App\Controllers\Traits\UserLoaderTrait;
use App\CoreUtils;
use App\DB;
use App\DeviantArt;
use App\File;
use App\HTTP;
use App\Models\DeviantartUser;
use App\Models\PreviousUsername;
use App\Models\User;
use App\Pagination;
use App\Permission;
use App\Response;
use App\UserPrefs;
use App\Users;
use OpenApi\Annotations as OA;
use RuntimeException;
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
