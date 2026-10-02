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
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="UserRole",
 *   type="string",
 *   description="List of roles a user can have",
 *   enum={"guest","user","member","assistant","staff","admin","developer"}
 * )
 * @OA\Schema(
 *   schema="AccountRole",
 *   type="string",
 *   description="The role of a user account (`guest` only exists as a permission level for signed-out visitors). A developer is shown under the label of the `dev_role_label` setting on public payloads.",
 *   enum={"user","member","assistant","staff","admin","developer"}
 * )
 * @OA\Schema(
 *   schema="AvatarProvider",
 *   type="string",
 *   description="Where a user's avatar comes from. Winterchilla only knows DeviantArt avatars; other implementations may also use Discord or Gravatar.",
 *   enum={"deviantart","discord","gravatar"}
 * )
 * @OA\Schema(
 *   schema="ValueOfUser",
 *   type="object",
 *   description="A user's data under the user key",
 *   required={
 *     "user"
 *   },
 *   additionalProperties=false,
 *   @OA\Property(
 *     property="user",
 *     type="object",
 *     ref="#/components/schemas/User"
 *   )
 * )
 * @OA\Schema(
 *   schema="SessionUpdating",
 *   type="object",
 *   required={
 *     "sessionUpdating"
 *   },
 *   additionalProperties=false,
 *   @OA\Property(
 *     property="sessionUpdating",
 *     type="boolean",
 *     description="If this value is true the DeviantArt access token expired and the backend is updating it in the background. Future requests should be made to the appropriate endpoint periodically (TODO) to check whether the session update was successful and the user should be logged out if it wasn't."
 *   )
 * )
 
 *
 * UsersAPIController
 
 */
class UsersAPIController extends APIController {
  /**
   * @OA\Schema(
   *   schema="User",
   *   type="object",
   *   description="Represents an authenticated user",
   *   required={
   *     "id",
   *     "name",
   *     "role",
   *     "avatarUrl",
   *     "avatarProvider"
   *   },
   *   additionalProperties=false,
   *   @OA\Property(
   *     property="id",
   *     ref="#/components/schemas/OneBasedId"
   *   ),
   *   @OA\Property(
   *     property="name",
   *     type="string",
   *     example="example"
   *   ),
   *   @OA\Property(
   *     property="role",
   *     ref="#/components/schemas/AccountRole",
   *   ),
   *   @OA\Property(
   *     property="avatarUrl",
   *     type="string",
   *     nullable=true,
   *     format="uri",
   *     example="https://a.deviantart.net/avatars/e/x/example.png"
   *   ),
   *   @OA\Property(
   *     property="avatarProvider",
   *     ref="#/components/schemas/AvatarProvider"
   *   )
   * )
   * @param User $u
   *
   * @return array
   */
  static function mapUser(User $u) {
    return [
      'id' => $u->id,
      'name' => $u->name,
      'role' => $u->role,
      'avatarUrl' => $u->avatar_url,
      'avatarProvider' => 'deviantart',
    ];
  }

  /**
   * @OA\Get(
   *   path="/users/me",
   *   description="Get information about the currently logged in user",
   *   tags={"authentication"},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ValueOfUser"),
   *         @OA\Schema(ref="#/components/schemas/SessionUpdating")
   *       }
   *     )
   *   ),
   *   @OA\Response(
   *     response="401",
   *     description="Not signed in",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   )
   * )
   */
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

  /**
   * @OA\Get(
   *   path="/users/{id}",
   *   security={},
   *   description="Get the public information of a user",
   *   tags={"users"},
   *   @OA\Parameter(in="path", name="id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/User")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  function getById(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $user = User::find((int)$params['id']);
    if ($user === null)
      Response::error(404, 'The user could not be found');

    Response::ok(self::mapPublicUser($user));
  }

  /**
   * @OA\Get(
   *   path="/users/da/{username}",
   *   security={},
   *   description="Get the public information of a user by their DeviantArt username. Unlike the profile page, this never asks DeviantArt about unknown names.",
   *   tags={"users"},
   *   @OA\Parameter(in="path", name="username", required=true, @OA\Schema(type="string")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/User")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  function getByName(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $da_user = DeviantartUser::find(['conditions' => ['name = ?', $params['username']]]);
    $user = $da_user?->user;
    if ($user === null)
      Response::error(404, 'The user could not be found');

    Response::ok(self::mapPublicUser($user));
  }

  /**
   * @OA\Get(
   *   path="/users/da-uuid/{uuid}",
   *   description="Get the public information of a user by their DeviantArt account UUID. Developer role only.",
   *   tags={"users"},
   *   @OA\Parameter(in="path", name="uuid", required=true, @OA\Schema(type="string", format="uuid")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/User")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
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

  /**
   * @OA\Get(
   *   path="/users",
   *   description="List the regular users of the site (id, name and role only). Staff only.",
   *   tags={"users"},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="array", @OA\Items(type="object", required={"id", "name", "role"}, additionalProperties=false,
   *       @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *       @OA\Property(property="name", type="string"),
   *       @OA\Property(property="role", ref="#/components/schemas/UserRole")
   *     ))
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Not staff", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
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

  /**
   * @OA\Schema(
   *   schema="UserProfile",
   *   type="object",
   *   description="Everything the profile page shows about a user, with what the current visitor may do with it",
   *   required={"user", "sameUser", "canEdit", "devOnDev", "editableRoles", "discordServerMember", "previousUsernames", "contributions", "contributionsCacheDuration", "personalGuides", "awaitingApproval"},
   *   additionalProperties=false,
   *   @OA\Property(property="user", ref="#/components/schemas/User"),
   *   @OA\Property(property="sameUser", type="boolean", description="Whether the visitor is looking at their own profile"),
   *   @OA\Property(property="canEdit", type="boolean", description="Whether the visitor may change this user's role"),
   *   @OA\Property(property="devOnDev", type="boolean", description="Whether a developer is looking at a developer (may change the displayed role label)"),
   *   @OA\Property(property="editableRoles", type="object", nullable=true, additionalProperties=@OA\AdditionalProperties(type="string"), description="Roles the visitor may assign, key to label"),
   *   @OA\Property(property="discordServerMember", type="boolean"),
   *   @OA\Property(property="previousUsernames", type="array", nullable=true, @OA\Items(type="string"), description="Only sent to the user themselves and to staff"),
   *   @OA\Property(
   *     property="contributions",
   *     type="array",
   *     @OA\Items(type="object", required={"type", "count", "noun", "verb"}, @OA\Property(property="type", type="string"), @OA\Property(property="count", type="integer"), @OA\Property(property="noun", type="string"), @OA\Property(property="verb", type="string"))
   *   ),
   *   @OA\Property(property="contributionsCacheDuration", type="string", example="hour"),
   *   @OA\Property(
   *     property="personalGuides",
   *     type="array",
   *     nullable=true,
   *     description="Null when the user keeps their personal guide section private from the visitor",
   *     @OA\Items(type="object", required={"id", "label", "private", "previewData"}, @OA\Property(property="id", ref="#/components/schemas/OneBasedId"), @OA\Property(property="label", type="string"), @OA\Property(property="private", type="boolean"), @OA\Property(property="previewData", type="array", @OA\Items(type="string")))
   *   ),
   *   @OA\Property(property="awaitingApproval", type="array", nullable=true, description="Finished posts waiting for approval; null when the user is not a member", @OA\Items(ref="#/components/schemas/PostItem"))
   * )
   * @OA\Get(
   *   path="/users/{id}/profile",
   *   security={},
   *   description="Get the data of a user's profile page",
   *   tags={"users"},
   *   @OA\Parameter(in="path", name="id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/UserProfile")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
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

  /**
   * @OA\Get(
   *   path="/users/{id}/contributions/{type}",
   *   security={},
   *   description="List one kind of a user's contributions, newest concerns first. `requests` is only visible to the user and to staff.",
   *   tags={"users"},
   *   @OA\Parameter(in="path", name="id", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Parameter(in="path", name="type", required=true, @OA\Schema(type="string", enum={"cms-provided", "requests", "reservations", "finished-posts", "fulfilled-requests"})),
   *   @OA\Parameter(in="query", name="page", @OA\Schema(type="integer", minimum=1, default=1)),
   *   @OA\Parameter(in="query", name="size", @OA\Schema(type="integer", minimum=1, maximum=50, default=10)),
   *   @OA\Response(
   *     response="200",
   *     description="OK. `cms-provided` items are `{appearance, favMe}`, every other type lists posts.",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"type", "items", "pagination"},
   *       @OA\Property(property="type", type="string"),
   *       @OA\Property(property="items", type="array", @OA\Items(oneOf={@OA\Schema(ref="#/components/schemas/PostItem"), @OA\Schema(type="object", required={"appearance", "favMe"}, @OA\Property(property="appearance", ref="#/components/schemas/PreviewAppearance"), @OA\Property(property="favMe", type="string", nullable=true))})),
   *       @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="The requests of another user (401 when signed out)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="User not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Invalid query", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
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
