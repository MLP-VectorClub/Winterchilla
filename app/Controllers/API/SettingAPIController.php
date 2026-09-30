<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\GlobalSettings;
use App\Permission;
use App\Response;
use Exception;
use OpenApi\Annotations as OA;

class SettingAPIController extends APIController {
  public function __construct() {
    parent::__construct();

    if (!Auth::$signed_in)
      Response::error(401);
    if (Permission::insufficient('staff'))
      Response::error(403);
  }

  private $setting, $value;

  public function load_setting($params) {
    $this->setting = $params['key'];
    if (!isset(GlobalSettings::DEFAULTS[$this->setting]))
      Response::error(404, "Unknown setting {$this->setting}");
    $this->value = GlobalSettings::get($this->setting);
  }

  /**
   * @OA\Get(
   *   path="/setting/{key}",
   *   description="Get the value of a global site setting. Requires staff role",
   *   tags={"settings"},
   *   @OA\Parameter(
   *     name="key",
   *     in="path",
   *     required=true,
   *     @OA\Schema(type="string", enum={"reservation_rules","about_reservations","dev_role_label"})
   *   ),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       required={"value"},
   *       additionalProperties=false,
   *       @OA\Property(property="value", type="string")
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Unknown setting key", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   * @OA\Put(
   *   path="/setting/{key}",
   *   description="Update the value of a global site setting. Requires staff role",
   *   tags={"settings"},
   *   @OA\Parameter(
   *     name="key",
   *     in="path",
   *     required=true,
   *     @OA\Schema(type="string", enum={"reservation_rules","about_reservations","dev_role_label"})
   *   ),
   *   @OA\RequestBody(@OA\JsonContent(
   *     required={"value"},
   *     additionalProperties=false,
   *     @OA\Property(property="value", type="string")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       required={"value"},
   *       additionalProperties=false,
   *       @OA\Property(property="value", type="string")
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Unknown setting key", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
   *   @OA\Response(response="500", description="The value could not be saved", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function api($params) {
    $this->load_setting($params);

    switch ($this->action){
      case 'GET':
        Response::ok(['value' => $this->value]);
      break;
      case 'PUT':
        $this->load_setting($params);

        if (!isset($_REQUEST['value']))
          Response::error(422, 'The given data was invalid.', ['errors' => ['value' => ['The value field is required.']]]);

        try {
          $newvalue = GlobalSettings::process($this->setting);
        }
        catch (Exception $e){
          Response::error(422, 'The given data was invalid.', ['errors' => ['value' => [$e->getMessage()]]]);
        }

        if ($newvalue === $this->value)
          Response::ok(['value' => $newvalue ?? $this->value]);
        if (!GlobalSettings::set($this->setting, $newvalue))
          Response::dbError(status: 500);

        // An empty value resets the setting, so report the effective (default) value
        Response::ok(['value' => GlobalSettings::get($this->setting)]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
