<?php

namespace App\Controllers;

use App\CoreUtils;
use App\DB;
use App\Pagination;
use App\Permission;
use App\Tags;

class TagController extends ColorGuideController {
  public function list() {
    $pagination = new Pagination('/cg/tags', 50, DB::$instance->count('tags'));

    CoreUtils::fixPath($pagination->toURI());
    $heading = 'All Tags';
    $title = "Page {$pagination->getPage()} - $heading - Color Guide";

    $tags = Tags::get($pagination->getLimit());

    $js = ['paginate'];
    if (Permission::sufficient('staff'))
      $js[] = true;

    CoreUtils::loadPage('ColorGuideController::tagList', [
      'title' => $title,
      'heading' => $heading,
      'css' => [true],
      'js' => $js,
      'import' => [
        'tags' => $tags,
        'pagination' => $pagination,
      ],
    ]);
  }
}
