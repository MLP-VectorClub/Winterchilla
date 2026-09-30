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

    if (!Auth::$signed_in)
      Response::error(401);
    if (empty($params['id']))
      CoreUtils::notFound();
    if (!array_key_exists($this->preference, UserPrefs::DEFAULTS))
      Response::error(404, "Unknown preference {$this->preference}");
    $user = User::find($params['id']);
    if (empty($user))
      Response::error(404, 'The specified user does not exist');
    if (Auth::$user->id !== $user->id && Permission::insufficient('staff'))
      Response::denied();

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
   *     @OA\JsonContent(ref="#/components/schemas/PreferenceValue")
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission, or not the same user and not staff",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist, or missing user ID",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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
   *     @OA\JsonContent(ref="#/components/schemas/PreferenceValue")
   *   ),
   *   @OA\Response(
   *     response="422",
   *     description="The new preference value is invalid",
   *     @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="500",
   *     description="The preference could not be saved due to a database error",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission, or not the same user and not staff",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified user does not exist, or missing user ID",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   )
   * )
   */
  public function api($params) {
    $this->load_preference($params);

    switch ($this->action){
      case 'GET':
        Response::ok(['value' => $this->value]);
      break;
      case 'PUT':
        try {
          $newvalue = UserPrefs::process($this->preference);
        }
        catch (Exception $e){
          Response::invalid('value', $e->getMessage());
        }

        if ($newvalue === $this->value)
          Response::ok(['value' => $newvalue]);
        if (!UserPrefs::set($this->preference, $newvalue, $this->user))
          Response::dbError(status: 500);

        Response::ok(['value' => $newvalue]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
