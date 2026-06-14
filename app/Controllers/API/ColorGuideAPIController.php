<?php

namespace App\Controllers\API;

use App\Appearances;
use App\CGUtils;
use App\Controllers\Traits\ColorGuideAccessTrait;
use App\CoreUtils;
use App\Input;
use App\Permission;
use App\Response;
use OpenApi\Annotations as OA;

class ColorGuideAPIController extends APIController {
  use ColorGuideAccessTrait;

  /**
   * @OA\Post(
   *   path="/cg/full/reorder",
   *   description="Reorder the appearances in a guide's full list. Staff only.",
   *   tags={"color guide"},
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"list"},
   *     @OA\Property(property="list", type="array", description="Appearance IDs in the desired order", @OA\Items(ref="#/components/schemas/OneBasedId")),
   *     @OA\Property(property="ordering", type="string", enum={"label","relevance","added"}, description="Sort order used to render the returned list")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(allOf={
   *       @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *       @OA\Schema(type="object", additionalProperties=false,
   *         @OA\Property(property="html", type="string", description="Rendered HTML of the full list")
   *       )
   *     })
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="400", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function reorderFullList($params):void {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    $this->_initialize($params);

    if (Permission::insufficient('staff'))
      Response::fail();

    Appearances::reorder((new Input('list', 'int[]', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The list of IDs is missing',
        Input::ERROR_INVALID => 'The list of IDs is not formatted properly',
      ],
    ]))->out());

    $ordering = (new Input('ordering', 'string', [
      Input::IS_OPTIONAL => true,
    ]))->out();

    Response::done(['html' => CGUtils::getFullListHTML(Appearances::get($this->guide), $ordering, $this->guide, NOWRAP)]);
  }

  /**
   * @OA\Get(
   *   path="/cg/export",
   *   description="Download the full color guide export data as a JSON file. Developer permission required.",
   *   tags={"color guide"},
   *   @OA\Response(
   *     response="200",
   *     description="The color guide export JSON file",
   *     @OA\MediaType(mediaType="application/json")
   *   ),
   *   @OA\Response(response="403", description="Insufficient permission (developer required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function export():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('developer'))
      CoreUtils::noPerm();

    CoreUtils::downloadAsFile(CGUtils::getExportData(), 'mlpvc-colorguide.json');
  }

  /**
   * @OA\Post(
   *   path="/cg/reindex",
   *   description="Trigger a full reindex of the color guide search index. Developer permission required.",
   *   tags={"color guide"},
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/ServerResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (developer required)", @OA\JsonContent(ref="#/components/schemas/ServerResponse"))
   * )
   */
  public function reindex():void {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (Permission::insufficient('developer'))
      Response::fail();
    Appearances::reindex();
  }
}
