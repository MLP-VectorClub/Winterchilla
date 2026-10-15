<?php

namespace App\Controllers;

use App\HTTP;

class DocsController extends Controller {
  /** The API documentation moved to Luna, which this API v0 was a stand-in for */
  public function index() {
    HTTP::permRedirect('https://api.mlpvector.club/');
  }
}
