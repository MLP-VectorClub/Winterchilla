<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\Response;
use App\UserPrefs;
use OpenApi\Annotations as OA;

class UserPrefsAPIController extends APIController {
  private const FLAGS = ['cg_hidesynon', 'cg_hideclrinfo', 'cg_fulllstprev', 'cg_nutshell', 'p_hidediscord', 'p_hidepcg', 'p_homelastep', 'ep_noappprev', 'ep_revstepbtn', 'a_pcgearn', 'a_pcgmake', 'a_pcgsprite', 'a_postreq', 'a_postres', 'a_reserve'];

  /**
   * @OA\Get(
   *   path="/user-prefs/me",
   *   description="Get the effective preference values of the current user (defaults for signed-out visitors). Send `keys[]` to limit the result.",
   *   tags={"user preferences"},
   *   security={},
   *   @OA\Parameter(in="query", name="keys[]", @OA\Schema(type="array", minItems=1, @OA\Items(type="string"))),
   *   @OA\Response(response="200", description="Preference key to value", @OA\JsonContent(ref="#/components/schemas/UserPrefs")),
   *   @OA\Response(response="422", description="Unknown or repeated key", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function me():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $keys = $_GET['keys'] ?? null;
    if ($keys === null)
      $keys = array_keys(UserPrefs::DEFAULTS);
    else {
      if (!is_array($keys) || count($keys) === 0 || count($keys) !== count(array_unique($keys)))
        Response::invalid('keys', 'The keys must be a non-empty list without duplicates.');
      foreach ($keys as $key) {
        if (!is_string($key) || !array_key_exists($key, UserPrefs::DEFAULTS))
          Response::invalid('keys', 'One of the selected keys is invalid.');
      }
    }

    $user = Auth::$signed_in ? Auth::$user : null;
    $result = [];
    foreach ($keys as $key) {
      $value = UserPrefs::get($key, $user);
      // The on/off preferences are stored as 0/1; the API speaks booleans
      $result[$key] = in_array($key, self::FLAGS, true) ? (bool)$value : $value;
    }

    Response::ok($result);
  }
}
