<?php

namespace App\Controllers;

use App\CGUtils;
use App\CoreUtils;
use App\HTTP;
use App\Models\TagChange;
use App\Pagination;
use App\Permission;
use App\Regexes;
use App\Response;
use App\Tags;
use function count;

class AppearanceController extends ColorGuideController {
  public function view($params):void {
    if ($this->owner === null)
      $this->_initialize($params);
    $this->load_appearance($params);

    if ($this->appearance->hidden())
      CoreUtils::noPerm();

    CoreUtils::fixPath($this->appearance->toURL());

    $cm_count = count($this->appearance->cutiemarks);
    $cmv = $cm_count > 0 ? ' and cutie mark '.CoreUtils::makePlural('vector', $cm_count) : '';

    $guide_name = ($this->owner ? CoreUtils::posess($this->owner->name).' Personal' : CGUtils::GUIDE_MAP[$this->guide]).' Color Guide';

    $settings = [
      'title' => "{$this->appearance->label} - $guide_name",
      'heading' => $this->appearance->getBabelLabel(),
      'css' => ['pages/colorguide/guide', true],
      'js' => ['jquery.ctxmenu', 'pages/colorguide/guide', true],
      'og' => [
        'image' => $this->appearance->getSpriteURL(),
        'description' => "Show accurate colors$cmv for \"{$this->appearance->label}\" from the MLP-VectorClub's Official Color Guide",
        'tags' =>
          ($cm_count > 0 ? 'cutie mark,cm,cm vector,cutie mark vector,' : '').
          $this->appearance->getTagsAsText(null, ',').
          ',color guide,colors,swatch file,illustrator swatches,gimp palette,inkscape swatches,png download',
      ],
      'import' => [
        'appearance' => $this->appearance,
        'guide' => $this->guide,
        'is_owner' => false,
      ],
    ];
    if (!empty($this->appearance->owner_id)){
      $settings['import']['owner'] = $this->owner;
      $settings['import']['is_owner'] = $this->is_owner;
      $settings['og']['description'] = "Colors$cmv for \"{$this->appearance->label}\" from ".CoreUtils::posess($this->owner->name)." Personal Color Guide on the the MLP-VectorClub's website";
    }
    if ($this->is_owner || Permission::sufficient('staff')){
      self::_appendManageAssets($settings);
      $settings['import']['exports'] = [
        'TAG_TYPES_ASSOC' => Tags::TAG_TYPES,
        'TAG_NAME_REGEX' => Regexes::$tag_name,
        'MAX_SIZE' => CoreUtils::getMaxUploadSize(),
        'HEX_COLOR_PATTERN' => Regexes::$hex_color,
      ];
    }
    CoreUtils::loadPage('ColorGuideController::appearance', $settings);
  }

  public function viewPersonal($params):void {
    $this->_initialize($params);
    if ($this->owner === null)
      CoreUtils::notFound();

    $this->view($params);
  }

  public function tagChanges($params):void {
    // TODO Finish feature
    CoreUtils::notFound();

    if (Permission::insufficient('staff'))
      Response::fail();

    $this->_initialize($params);
    $this->load_appearance($params);

    if ($this->appearance->owner_id !== null)
      CoreUtils::notFound();

    $totalChangeCount = TagChange::count(['appearance_id' => $this->appearance->id]);
    /** @noinspection PhpUnusedLocalVariableInspection */
    $Pagination = new Pagination("{$this->path}/tag-changes/{$this->appearance->getURLSafeLabel()}", 25, $totalChangeCount);
  }

  public function asFile($params):void {
    $this->_initialize($params);
    $this->load_appearance($params);

    if ($this->appearance->hidden())
      CoreUtils::notFound();

    switch ($params['ext']){
      case 'png':
        switch ($params['type']){
          case 's':
            HTTP::tempRedirect($this->appearance->getSpriteURL());
          case 'p':
          default:
            CGUtils::renderAppearancePNG($this->path, $this->appearance);
        }
      break;
      case 'svg':
        if (!empty($params['type'])) switch ($params['type']){
          case 's':
            CGUtils::renderSpriteSVG($this->path, $this->appearance);
          case 'p':
            CGUtils::renderPreviewSVG($this->appearance);
          case 'f':
            CGUtils::renderCMFacingSVG($this->appearance);
          default:
            CoreUtils::notFound();
        }
      case 'json':
        CGUtils::getSwatchesAI($this->appearance);
      case 'gpl':
        CGUtils::getSwatchesInkscape($this->appearance);
    }
    # rendering functions internally call die(), so execution stops above #

    CoreUtils::notFound();
  }

}
