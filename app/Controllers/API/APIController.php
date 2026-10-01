<?php

namespace App\Controllers\API;

use App\Controllers\Controller;
use App\CoreUtils;
use OpenApi\Annotations as OA;

class APIController extends Controller {
  public function __construct() {
    CoreUtils::removeCSPHeaders();
    parent::__construct();
  }
}
