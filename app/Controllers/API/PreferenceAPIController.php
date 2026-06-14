<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\Models\User;
use App\Permission;
use App\Response;
use App\UserPrefs;
use Exception;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="PreferenceValue",
 *   type="object",
 *   description="The current (or newly set) value of a user preference",
 *   required={"value"},
 *   additionalProperties=false,
 *   @OA\Property(
 *     property="value",
 *     description="The preference value. Type depends on the specified preference key."
 *   )
 * )
 */
class PreferenceAPIController extends APIController {
  public function __construct() {
    parent::__construct();

    if (Permission::insufficient('user'))
      CoreUtils::noPerm();
  }

  private $value;
  private string $preference;
  private ?User $user;

  public function load_preference($params) {
    $this->preference = $params['key'];

    if (empty($params['id']))
      CoreUtils::notFound();
    $user = User::find($params['id']);
    if (empty($user))
      Response::fail('The specified user does not exist');
    if (Auth::$user->id !== $user->id && Permission::insufficient('staff'))
      Response::fail();

    $this->user = $user;
    $this->value = UserPrefs::get($this->preference, $this->user);
  }

  /**
   * @OA\Get(
   *   path="/user/{id}/preference/{key}",
   *   description="Gets the value of a preference for the specified user. Requires user permission, and the requester must be the same user or staff.",
   *   tags={"users"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Parameter(
   *     name="key",
   *     in="path",
   *     required=true,
   *     description="The preference key to retrieve",
   *     @OA\Schema(type="string")
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(ref="#/components/schemas/PreferenceValue")
   *       }
   *     )
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission, or not the same user and not staff",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist, or missing user ID",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   * @OA\Put(
   *   path="/user/{id}/preference/{key}",
   *   description="Sets the value of a preference for the specified user. Requires user permission, and the requester must be the same user or staff.",
   *   tags={"users"},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     required=true,
   *     @OA\Schema(ref="#/components/schemas/OneBasedId")
   *   ),
   *   @OA\Parameter(
   *     name="key",
   *     in="path",
   *     required=true,
   *     description="The preference key to update",
   *     @OA\Schema(type="string")
   *   ),
   *   @OA\RequestBody(
   *     required=true,
   *     description="The new preference value, processed and validated according to the specified preference key",
   *     @OA\MediaType(
   *       mediaType="application/x-www-form-urlencoded",
   *       @OA\Schema(type="object")
   *     )
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(ref="#/components/schemas/PreferenceValue")
   *       }
   *     )
   *   ),
   *   @OA\Response(
   *     response="400",
   *     description="The new preference value is invalid, or it could not be saved due to a database error",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission, or not the same user and not staff",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist, or missing user ID",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function api($params) {
    $this->load_preference($params);

    switch ($this->action){
      case 'GET':
        Response::done(['value' => $this->value]);
      break;
      case 'PUT':
        try {
          $newvalue = UserPrefs::process($this->preference);
        }
        catch (Exception $e){
          Response::fail('Preference value error: '.$e->getMessage());
        }

        if ($newvalue === $this->value)
          Response::done(['value' => $newvalue]);
        if (!UserPrefs::set($this->preference, $newvalue, $this->user))
          Response::dbError();

        Response::done(['value' => $newvalue]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
