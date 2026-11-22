<?php

namespace App\Controllers;

use App\CoreUtils;
use App\HTTP;
use App\LunaMedia;
use App\Models\Cutiemark;

class CutiemarkController extends ColorGuideController {
  public function view($params) {
    $cutiemark = Cutiemark::find($params['id']);
    if (empty($cutiemark) || $cutiemark->appearance->hidden())
      CoreUtils::notFound();

    HTTP::tempRedirect(LunaMedia::cutieMark($cutiemark->id));
  }

  public function download($params) {
    $cutiemark = Cutiemark::find($params['id']);
    if (empty($cutiemark) || $cutiemark->appearance->hidden())
      CoreUtils::notFound();

    HTTP::tempRedirect(LunaMedia::cutieMark($cutiemark->id, true));
  }
}
