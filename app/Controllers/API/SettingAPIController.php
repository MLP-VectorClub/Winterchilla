<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\GlobalSettings;
use App\Permission;
use App\Response;
use Exception;

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
