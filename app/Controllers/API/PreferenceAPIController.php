<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\Models\User;
use App\Permission;
use App\Response;
use App\UserPrefs;
use Exception;

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
