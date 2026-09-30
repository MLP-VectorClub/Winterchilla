<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\DeviantArt;
use App\GlobalSettings;
use App\Models\DeviantartUser;
use App\Models\User;
use App\Permission;
use App\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="UserRole",
 *   type="string",
 *   description="List of roles a user can have",
 *   enum={"guest","user","member","assistant","staff","admin","developer"}
 * )
 * @OA\Schema(
 *   schema="AvatarProvider",
 *   type="string",
 *   description="List of supported avatar providers",
 *   enum={"deviantart"}
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
   *     ref="#/components/schemas/UserRole",
   *   ),
   *   @OA\Property(
   *     property="avatarUrl",
   *     type="string",
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

  /** Same as mapUser(), but with the developer role replaced by its public label */
  static function mapPublicUser(User $u):array {
    $data = self::mapUser($u);
    $data['role'] = $u->maskedRole();

    return $data;
  }

  // TODO Endpoint for changing user settings
}
