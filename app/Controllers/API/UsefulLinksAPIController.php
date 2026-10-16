<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\Models\UsefulLink;
use App\Permission;
use App\Response;

class UsefulLinksAPIController extends APIController {
  public function sidebar():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (!Auth::$signed_in)
      Response::ok([]);

    $links = array_filter(
      UsefulLink::in_order(),
      fn(UsefulLink $l) => $l->minrole === 'guest' || Auth::$user->perm($l->minrole)
    );

    Response::ok(array_values(array_map(fn(UsefulLink $l) => [
      'id' => $l->id,
      'label' => $l->label,
      'url' => $l->url,
      'title' => $l->title,
      'minRole' => $l->minrole,
    ], $links)));
  }

  public function list():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();
    if (Permission::insufficient('staff'))
      Response::denied();

    Response::ok(array_map(fn(UsefulLink $l) => [
      'id' => $l->id,
      'label' => $l->label,
      'url' => $l->url,
      'title' => $l->title,
      'minRole' => $l->minrole,
    ], UsefulLink::in_order()));
  }
}
