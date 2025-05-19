<?php

namespace App\Exceptions;

use Exception;

class CURLRequestException extends Exception {
  public function __construct($errMsg, $errCode, string $curlError, public readonly ?string $responseHeaders = null, public readonly ?string $response = null) {
    parent::__construct("$errMsg (HTTP $errCode)", $errCode);
    if (!empty($curlError))
      $this->message .= "; $curlError";
  }
}
