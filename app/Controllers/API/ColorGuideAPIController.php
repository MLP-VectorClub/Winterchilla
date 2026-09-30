<?php

namespace App\Controllers\API;

use App\Appearances;
use App\CGUtils;
use App\Controllers\ColorGuideController;
use App\Controllers\Traits\ColorGuideAccessTrait;
use App\CoreUtils;
use App\Input;
use App\Permission;
use App\Response;
use OpenApi\Annotations as OA;

class ColorGuideAPIController extends APIController {
  use ColorGuideAccessTrait;

  /**
   * @OA\Put(
   *   path="/appearances/order",
   *   description="Reorder the appearances in a guide's full list. Staff only.",
   *   tags={"color guide"},
   *   @OA\RequestBody(required=true, @OA\JsonContent(
   *     required={"list","guide"},
   *     @OA\Property(property="guide", ref="#/components/schemas/GuideName", description="The guide whose full list is returned"),
   *     @OA\Property(property="list", type="array", description="Appearance IDs in the desired order", @OA\Items(ref="#/components/schemas/OneBasedId")),
   *     @OA\Property(property="ordering", type="string", enum={"label","relevance","added"}, description="Sort order used to render the returned list")
   *   )),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="object", additionalProperties=false,
   *         @OA\Property(property="html", type="string", description="Rendered HTML of the full list")
   *       )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (staff required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="422", description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function reorderFullList($params):void {
    if ($this->action !== 'PUT')
      CoreUtils::notAllowed();

    if (Permission::insufficient('staff'))
      Response::denied();

    // The guide comes with the request body (there is no route parameter for it)
    $guide = self::validateGuide();

    Appearances::reorder((new Input('list', 'int[]', [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The list of IDs is missing',
        Input::ERROR_INVALID => 'The list of IDs is not formatted properly',
      ],
    ]))->out());

    $ordering = (new Input('ordering', 'string', [
      Input::IS_OPTIONAL => true,
    ]))->out();

    Response::ok(['html' => CGUtils::getFullListHTML(Appearances::get($guide), $ordering, $guide, NOWRAP)]);
  }

  private static function validateGuide():string {
    return (new Input('guide', function ($value) {
      if (!isset(CGUtils::GUIDE_MAP[$value]))
        return Input::ERROR_INVALID;
    }, [
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'The guide is missing',
        Input::ERROR_INVALID => 'The guide (@value) is invalid',
      ],
    ]))->out();
  }

  /**
   * @OA\Get(
   *   path="/cg/full",
   *   description="Get the rendered full list of a guide in the requested order, along with the URL of the full list page for that order",
   *   tags={"color guide"},
   *   security={},
   *   @OA\Parameter(name="guide", in="query", required=true, @OA\Schema(ref="#/components/schemas/GuideName")),
   *   @OA\Parameter(name="sort_by", in="query", required=false, @OA\Schema(type="string", enum={"label","relevance","added"}, default="relevance")),
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(type="object", required={"html","stateUrl"},
   *       @OA\Property(property="html", type="string", description="Rendered HTML of the full list"),
   *       @OA\Property(property="stateUrl", type="string", description="URL of the full list page in this order")
   *     )
   *   ),
   *   @OA\Response(response="422", description="Missing or invalid guide", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function fullList():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $guide = self::validateGuide();
    [$appearances, $sort_by, $path] = ColorGuideController::getFullListData($guide, $_GET['sort_by'] ?? null, "/cg/$guide");

    Response::ok([
      'html' => CGUtils::getFullListHTML($appearances, $sort_by, $guide, NOWRAP),
      'stateUrl' => $path,
    ]);
  }

  /**
   * @OA\Get(
   *   path="/color-guide/export",
   *   description="Download the full color guide export data as a JSON file. Developer permission required.",
   *   tags={"color guide"},
   *   @OA\Response(
   *     response="200",
   *     description="The color guide export JSON file",
   *     @OA\MediaType(mediaType="application/json")
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (developer required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
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
   *   path="/color-guide/reindex",
   *   description="Trigger a full reindex of the color guide search index. Developer permission required.",
   *   tags={"color guide"},
   *   @OA\Response(response="200", description="Re-index completed", @OA\JsonContent(type="object", required={"message"}, @OA\Property(property="message", type="string"))),
   *   @OA\Response(response="503", description="The ElasticSearch server is unreachable", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="403", description="Insufficient permission (developer required)", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function reindex():void {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (Permission::insufficient('developer'))
      Response::denied();
    Appearances::reindex();
  }
}
