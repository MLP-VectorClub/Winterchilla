<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\DeviantArt;
use App\GlobalSettings;
use App\Models\Appearance;
use App\Models\DeviantartUser;
use App\Models\Post;
use App\Models\PreviousUsername;
use App\Models\User;
use App\Pagination;
use App\Permission;
use App\Response;
use App\UserPrefs;
use App\Users;

class UsersAPIController extends APIController {
  static function mapUser(User $u) {
    return [
      'id' => $u->id,
      'name' => $u->name,
      'role' => $u->role,
      'avatarUrl' => $u->avatar_url,
      'avatarProvider' => 'deviantart',
    ];
  }

  function me() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (!Auth::$signed_in)
      Response::error(401, 'The requested resource requires authentication');

    Response::ok([
      'user' => self::mapUser(Auth::$user),
      'sessionUpdating' => Auth::$session->updating,
    ]);
  }

  function getById(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $user = User::find((int)$params['id']);
    if ($user === null)
      Response::error(404, 'The user could not be found');

    Response::ok(self::mapPublicUser($user));
  }

  function getByName(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $da_user = DeviantartUser::find(['conditions' => ['name = ?', $params['username']]]);
    $user = $da_user?->user;
    if ($user === null)
      Response::error(404, 'The user could not be found');

    Response::ok(self::mapPublicUser($user));
  }

  function getByDeviantArtUuid(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();
    if (Permission::insufficient('developer'))
      Response::denied();

    $da_user = DeviantartUser::find($params['uuid']);
    $user = $da_user?->user;
    if ($user === null)
      Response::error(404, 'The user could not be found');

    Response::ok(self::mapPublicUser($user));
  }

  function list() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::denied();

    // The developer role may be labeled as a regular user
    $roles = GlobalSettings::get('dev_role_label') === 'user' ? ['user', 'developer'] : ['user'];
    $users = User::find('all', ['conditions' => ['role IN (?)', $roles], 'order' => 'name asc']);

    Response::ok(array_map(fn(User $u) => ['id' => $u->id, 'name' => $u->name, 'role' => 'user'], $users));
  }

  function profile(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $user = User::find((int)$params['id']);
    if ($user === null)
      Response::error(404, 'The user could not be found');

    $same_user = Auth::$signed_in && $user->id === Auth::$user->id;
    $is_staff = Permission::sufficient('staff');
    $can_edit = !$same_user && $is_staff && Permission::sufficient($user->role);
    $dev_on_dev = Permission::sufficient('developer') && Permission::sufficient('developer', $user->role);

    $editable_roles = null;
    if ($can_edit) {
      $editable_roles = [];
      foreach (Permission::ROLES_ASSOC as $name => $label) {
        if ($name !== 'guest' && Permission::sufficient($name, Auth::$user->role))
          $editable_roles[$name] = $label;
      }
    }
    else if ($dev_on_dev)
      $editable_roles = Permission::ROLES_ASSOC;

    $previous_names = null;
    if (($same_user || $is_staff) && $user->boundToDeviantartUser())
      $previous_names = array_map(fn(PreviousUsername $p) => $p->username, $user->deviantart_user->previous_names);

    $list_pcgs = !UserPrefs::get('p_hidepcg', $user) || $same_user || $is_staff;
    $guides = null;
    if ($list_pcgs) {
      $guides = array_map(fn(Appearance $a) => [
        'id' => $a->id,
        'label' => $a->label,
        'private' => (bool)$a->private,
        'previewData' => $a->private && !$same_user && !$is_staff ? [] : array_map(fn($c) => $c->hex, $a->getPreviewColors()),
      ], $user->pcg_appearances);
    }

    $contributions = [];
    foreach ($user->getCachedContributions() as $type => [$count, $noun, $verb])
      $contributions[] = ['type' => $type, 'count' => (int)$count, 'noun' => $noun, 'verb' => $verb];

    Response::ok([
      'user' => self::mapPublicUser($user),
      'sameUser' => $same_user,
      'canEdit' => $can_edit,
      'devOnDev' => $dev_on_dev,
      'editableRoles' => $editable_roles,
      'discordServerMember' => $user->isDiscordServerMember(),
      'previousUsernames' => $previous_names,
      'contributions' => $contributions,
      'contributionsCacheDuration' => Users::getContributionsCacheDuration(),
      'personalGuides' => $guides,
      'awaitingApproval' => $user->perm('member')
        ? array_map(fn(Post $p) => PostAPIController::mapPost($p), $user->getPostsAwaitingApproval())
        : null,
    ]);
  }

  function contributions(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $user = User::find((int)$params['id']);
    if ($user === null)
      Response::error(404, 'The user could not be found');
    $type = $params['type'];
    if ($type === 'requests' && $user->id !== (Auth::$user->id ?? null) && Permission::insufficient('staff'))
      Response::denied();

    $size = $_GET['size'] ?? 10;
    if (!is_numeric($size) || $size < 1 || $size > 50)
      Response::invalid('size', 'The size must be between 1 and 50.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $getters = [
      'cms-provided' => 'getCMContributions',
      'requests' => 'getRequestContributions',
      'reservations' => 'getReservationContributions',
      'finished-posts' => 'getFinishedPostContributions',
      'fulfilled-requests' => 'getApprovedFinishedRequestContributions',
    ];
    $getter = $getters[$type];
    $total = (int)$user->$getter();
    $pagination = (new Pagination('', $size))->forcePage($page)->calcMaxPages($total);
    $data = $user->$getter(false, $pagination);

    $items = array_map(function ($item) use ($type) {
      if ($type !== 'cms-provided')
        return PostAPIController::mapPost($item);

      return [
        'appearance' => AppearancesAPIController::mapPreviewAppearance(Appearance::find($item->appearance_id)),
        'favMe' => $item->favme,
      ];
    }, $data);

    Response::ok([
      'type' => $type,
      'items' => $items,
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
    ]);
  }

  /** Same as mapUser(), but with the developer role replaced by its public label */
  static function mapPublicUser(User $u):array {
    $data = self::mapUser($u);
    $data['role'] = $u->maskedRole();

    return $data;
  }

  // TODO Endpoint for changing user settings
}
