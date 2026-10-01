<?php

namespace App\Controllers;

use App\CoreUtils;
use App\CSRFProtection;
use App\Users;
use function is_array;

abstract class Controller {
  protected static $auth = true;
  /** @var string */
  protected $path;
  /** @var string */
  protected $action;
  /** @var bool */
  protected $creating;

  public function __construct() {
    $this->action = $_SERVER['REQUEST_METHOD'];
    $this->creating = $this->action === 'POST';

    // The API accepts JSON bodies as well as form-encoded ones: JSON values are turned into the form representation the inputs expect
    $json_body = self::readJsonBody();
    if ($json_body !== null) {
      foreach ($json_body as $key => $value)
        $_REQUEST[$key] = $_POST[$key] = $value;
    }

    switch ($this->action){
      case 'PUT':
      case 'DELETE':
        if ($json_body !== null)
          break;
        $in = file_get_contents('php://input');
        parse_str($in, $data);

        if (is_array($data) && !empty($data))
          foreach ($data as $k => $v){
            if (is_numeric($k))
              $_REQUEST[] = $v;
            else $_REQUEST[$k] = $v;
          }
      break;
    }

    CSRFProtection::protect();
    if (static::$auth) {
      Users::authenticate();
      CoreUtils::checkNutshell();
    }
  }

  /**
   * Decodes an `application/json` request body. Scalars become strings (booleans `true`/`false`), a list of scalars becomes a
   * comma-separated list (`[1,2]` → `1,2`, what the `int[]` inputs read), and anything nested is kept as a JSON string (what the
   * `json` inputs read). `null` means the field is absent.
   *
   * @return array<string, string>|null null when the request has no JSON body
   */
  private static function readJsonBody():?array {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'], true))
      return null;
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0)
      return null;
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (!is_array($decoded) || array_is_list($decoded) && !empty($decoded))
      return null;

    $result = [];
    foreach ($decoded as $key => $value) {
      if ($value === null)
        continue;
      if (is_bool($value))
        $result[$key] = $value ? 'true' : 'false';
      else if (is_scalar($value))
        $result[$key] = (string)$value;
      else if (array_is_list($value) && count(array_filter($value, 'is_scalar')) === count($value))
        $result[$key] = implode(',', array_map(fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : (string)$v, $value));
      else $result[$key] = json_encode($value);
    }

    return $result;
  }
}
