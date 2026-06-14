<?php

namespace App\Controllers;

use App\CGUtils;
use App\Controllers\Traits\UserLoaderTrait;
use App\CoreUtils;
use App\Pagination;
use App\Permission;
use App\Regexes;
use App\UserPrefs;
use function count;

class PersonalGuideController extends ColorGuideController {
  use UserLoaderTrait;

  public function list($params) {
    $this->_initialize($params);

    if (!$this->owner->canVisitorSeePCG())
      CoreUtils::noPerm();

    $AppearancesPerPage = UserPrefs::get('cg_itemsperpage');
    $_EntryCount = $this->owner->getPCGAppearanceCount();

    $pagination = new Pagination($this->path, $AppearancesPerPage, $_EntryCount);
    $appearances = $this->owner->getPCGAppearances($pagination);

    CoreUtils::fixPath($pagination->toURI());
    $heading = CoreUtils::posess($this->owner->name).' Personal Color Guide';
    $title = "Page {$pagination->getPage()} - $heading";

    $is_owner = $this->is_owner;
    $owner_or_staff = $is_owner || Permission::sufficient('staff');

    $settings = [
      'title' => $title,
      'heading' => $heading,
      'css' => ['pages/colorguide/guide'],
      'js' => ['jquery.ctxmenu', 'pages/colorguide/guide', 'paginate'],
      'import' => [
        'appearances' => $appearances,
        'pagination' => $pagination,
        'user' => $this->owner,
        'is_owner' => $is_owner,
        'owner_or_staff' => $owner_or_staff,
        'max_upload_size' => CoreUtils::getMaxUploadSize(),
      ],
    ];
    if ($owner_or_staff){
      self::_appendManageAssets($settings);
      $settings['import']['hex_color_regex'] = Regexes::$hex_color;
    }
    CoreUtils::loadPage('UserController::colorguide', $settings);
  }

  public function pointHistory($params) {
    $this->_initialize($params);

    if (!$this->is_owner && Permission::insufficient('staff'))
      CoreUtils::noPerm();

    $EntriesPerPage = 20;
    $_EntryCount = $this->owner->getPCGSlotHistoryEntryCount();

    $pagination = new Pagination("{$this->path}/point-history", $EntriesPerPage, $_EntryCount);
    $entries = $this->owner->getPCGSlotHistoryEntries($pagination);
    if (count($entries) === 0){
      $this->owner->recalculatePCGSlotHistroy();
      $entries = $this->owner->getPCGSlotHistoryEntries($pagination);
    }

    CoreUtils::fixPath($pagination->toURI());
    $heading = ($this->is_owner ? 'Your' : CoreUtils::posess($this->owner->name)).' Point History';
    $title = "Page {$pagination->getPage()} - $heading";

    $js = ['paginate'];
    if (Permission::sufficient('staff'))
      $js[] = true;
    CoreUtils::loadPage('UserController::pcgSlots', [
      'title' => $title,
      'heading' => $heading,
      'css' => [true],
      'js' => $js,
      'import' => [
        'entries' => $entries,
        'pagination' => $pagination,
        'user' => $this->owner,
        'is_owner' => $this->is_owner,
        'pcg_slot_history' => CGUtils::getPCGSlotHistoryHTML($entries),
      ],
    ]);
  }
}
