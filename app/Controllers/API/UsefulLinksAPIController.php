<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\Models\UsefulLink;
use App\Response;
use OpenApi\Annotations as OA;

class UsefulLinksAPIController extends APIController {
  /**
   * @OA\Schema(
   *   schema="UsefulLink",
   *   type="object",
   *   required={"id", "label", "url", "minRole"},
   *   additionalProperties=false,
   *   @OA\Property(property="id", ref="#/components/schemas/OneBasedId"),
   *   @OA\Property(property="label", type="string"),
   *   @OA\Property(property="url", type="string"),
   *   @OA\Property(property="title", type="string", nullable=true),
   *   @OA\Property(property="minRole", type="string", description="The lowest role that can see this link")
   * )
   * @OA\Get(
   *   path="/useful-links/sidebar",
   *   description="Get the links shown in the sidebar for the current user, in display order. Signed-out visitors get an empty list.",
   *   tags={"useful links"},
   *   security={},
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/UsefulLink")))
   * )
   */
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
}
