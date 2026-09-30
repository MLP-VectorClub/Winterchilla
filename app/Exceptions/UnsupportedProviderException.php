<?php

namespace App\Exceptions;

use Exception;

class UnsupportedProviderException extends Exception {
  public function __construct() {
    parent::__construct("Unsupported provider. Try uploading your image to Sta.sh (http://sta.sh)");
  }
}
