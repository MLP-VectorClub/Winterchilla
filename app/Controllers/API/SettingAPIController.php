<?php

namespace App\Controllers\API;

use App\CoreUtils;
use App\GlobalSettings;
use App\Permission;
use App\Response;
use Exception;
use OpenApi\Annotations as OA;

class SettingAPIController extends APIController {
  public function __construct() {
    parent::__construct();

    if (Permission::insufficient('staff'))
      CoreUtils::noPerm();
  }

  private $setting, $value;

  public function load_setting($params) {
    $this->setting = $params['key'];
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
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(
   *         required={"value"},
   *         additionalProperties=false,
   *         @OA\Property(property="value", type="string")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
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
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(
   *         required={"value"},
   *         additionalProperties=false,
   *         @OA\Property(property="value", type="string")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permissions", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="default", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function api($params) {
    $this->load_setting($params);

    switch ($this->action){
      case 'GET':
        Response::done(['value' => $this->value]);
      break;
      case 'PUT':
        $this->load_setting($params);

        if (!isset($_REQUEST['value']))
          Response::fail('Missing setting value');

        try {
          $newvalue = GlobalSettings::process($this->setting);
        }
        catch (Exception $e){
          Response::fail('Preference value error: '.$e->getMessage());
        }

        if ($newvalue === $this->value)
          Response::done(['value' => $newvalue]);
        if (!GlobalSettings::set($this->setting, $newvalue))
          Response::dbError();

        Response::done(['value' => $newvalue]);
      break;
      default:
        CoreUtils::notAllowed();
    }
  }
}
