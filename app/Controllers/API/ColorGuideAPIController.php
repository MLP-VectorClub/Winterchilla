<?php

namespace App\Controllers\API;

use App\Appearances;
use App\CGUtils;
use App\Controllers\ColorGuideController;
use App\Controllers\Traits\ColorGuideAccessTrait;
use App\CoreUtils;
use App\Input;
use App\Models\Appearance;
use App\Models\MajorChange;
use App\Permission;
use App\Response;

class ColorGuideAPIController extends APIController {
  use ColorGuideAccessTrait;

  public function index():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $counts = [];
    foreach (array_keys(CGUtils::GUIDE_MAP) as $guide)
      $counts[$guide] = Appearance::count(['conditions' => ['guide = ?', $guide]]);

    Response::ok(['entryCounts' => $counts]);
  }

  public function majorChanges():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $guide = self::validateGuide();
    $size = $_GET['size'] ?? 9;
    if (!is_numeric($size) || $size < 1 || $size > 15)
      Response::invalid('size', 'The size must be between 1 and 15.');
    $size = (int)$size;
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');
    $page = (int)$page;

    $total = MajorChange::total($guide);
    $is_staff = Permission::sufficient('staff');
    $changes = MajorChange::get(null, $guide, 'LIMIT '.$size.' OFFSET '.(($page - 1) * $size));

    Response::ok([
      'changes' => array_map(fn(MajorChange $mc) => [
        'id' => $mc->id,
        'reason' => $mc->reason,
        'appearance' => AppearancesAPIController::mapPreviewAppearance($mc->appearance),
        'user' => $is_staff && $mc->user !== null ? UsersAPIController::mapUser($mc->user) : null,
        'createdAt' => gmdate('c', $mc->created_at->getTimestamp()),
      ], $changes),
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
    ]);
  }

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

  public function export():void {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Permission::insufficient('developer'))
      CoreUtils::noPerm();

    CoreUtils::downloadAsFile(CGUtils::getExportData(), 'mlpvc-colorguide.json');
  }

  public function reindex():void {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (Permission::insufficient('developer'))
      Response::denied();
    Appearances::reindex();
  }
}
