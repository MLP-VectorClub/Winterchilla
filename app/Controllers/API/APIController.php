<?php

namespace App\Controllers\API;

use App\Controllers\Controller;
use App\CoreUtils;

class APIController extends Controller {
  public function __construct() {
    CoreUtils::removeCSPHeaders();
    parent::__construct();
  }
}
