<?php

namespace App\Controllers\API;

use App\Appearances;
use App\Auth;
use App\CGUtils;
use App\CoreUtils;
use App\DB;
use App\HTTP;
use App\Models\Appearance;
use App\Models\Color;
use App\Models\ColorGroup;
use App\Models\Cutiemark;
use App\Models\PinnedAppearance;
use App\Models\Show;
use App\Models\Tag;
use App\Models\TagChange;
use App\Pagination;
use App\Permission;
use App\RedisHelper;
use App\Response;
use App\Tags;
use function count;

class AppearancesAPIController extends APIController {
  static function mapAppearance(Appearance $a, bool $with_previews, bool $compact = false) {
    $tags = array_map(function (Tag $t) {
      return self::mapTag($t);
    }, Tags::getFor($a->id, null, true, true));

    $appearance = [
      'id' => $a->id,
      'guide' => $a->guide,
      'ownerId' => $a->owner_id === null ? null : (int)$a->owner_id,
      'previewData' => array_map(fn(Color $c) => $c->hex, $a->getPreviewColors()),
      'label' => $a->label,
      'createdAt' => gmdate('c', $a->created_at->getTimestamp()),
      'notes' => $a->notes_rend,
      'tags' => $tags,
      'sprite' => self::mapSprite($a, $with_previews),
      'hasCutieMarks' => count($a->cutiemarks) !== 0,
    ];
    if (!$compact)
      $appearance['colorGroups'] = self::_getColorGroups($a);

    return $appearance;
  }

  private static function _getColorGroups(Appearance $a):array {
    $colors = CGUtils::getColorsForEach($a->color_groups);
    $color_groups = array_map(function (ColorGroup $cg) use ($colors) {
      return self::mapColorGroup($cg, $colors);
    }, $a->color_groups);

    return $color_groups;
  }

  static function mapTag(Tag $t) {
    return [
      'id' => $t->id,
      'name' => $t->name,
      'type' => $t->type,
    ];
  }

  static function mapSprite(Appearance $a, $with_preview = false):?array {
    if (!$a->hasSprite())
      return null;

    $value = ['hash' => $a->sprite_hash];
    if ($with_preview)
      $value['preview'] = CoreUtils::bin2dataUri(CGUtils::generateSpritePreview($a->id, $a->owner_id !== null), 'image/png');

    return $value;
  }

  static function mapColorGroup(ColorGroup $cg, array $color_map) {
    $colors = array_map(function (Color $c) {
      return self::mapColor($c);
    }, $color_map[$cg->id]);

    return [
      'id' => $cg->id,
      'label' => $cg->label,
      'order' => $cg->order,
      'colors' => $colors,
    ];
  }

  static function mapColor(Color $c) {
    return [
      'id' => $c->id,
      'label' => $c->label,
      'order' => $c->order,
      'hex' => $c->hex,
      'linkedTo' => null,
    ];
  }

  function queryPublic() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $elastic_avail = CGUtils::isElasticAvailable();
    if (!$elastic_avail)
      Response::error(503, 'The OpenSearch server is unreachable');
    if (isset($_GET['size']) && is_numeric($_GET['size']))
      $appearances_per_page = CoreUtils::rangeLimit((int)$_GET['size'], 7, 20);
    else $appearances_per_page = 7;
    $pagination = new Pagination('', $appearances_per_page);
    $searching = !empty($_GET['q']) && $_GET['q'] !== '';
    $guide_name = $_GET['guide'] ?? null;
    $with_previews = ($_GET['previews'] ?? null) === 'true';
    if (!array_key_exists($guide_name, CGUtils::GUIDE_MAP))
      Response::invalid('guide', 'The selected guide is invalid.');
    [$appearances] = CGUtils::searchGuide($pagination, $guide_name, $searching);

    $results = array_map(function (Appearance $a) use ($with_previews) {
      return self::mapAppearance($a, $with_previews);
    }, $appearances);
    Response::ok([
      'appearances' => $results,
      'pagination' => CoreUtils::paginationForApi($pagination),
    ]);
  }

  function queryAll() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $guide_name = $_GET['guide'] ?? null;
    $with_previews = ($_GET['previews'] ?? null) === 'true';
    if (!isset(CGUtils::GUIDE_MAP[$guide_name]))
      Response::invalid('guide', 'The selected guide is invalid.');

    $sort = $_GET['sort'] ?? 'relevance';
    if (!in_array($sort, ['relevance', 'label', 'added'], true))
      Response::invalid('sort', 'The sort field must be relevance, label or added.');

    $cache_time = 600;
    $cache_key = CoreUtils::generateCacheKey(4, 'all appearances', $guide_name, $with_previews, $sort);
    $cached_data = RedisHelper::get($cache_key);
    if ($cached_data !== null)
      Response::doneCached($cached_data);

    switch ($sort) {
      case 'label':
        DB::$instance->orderBy('label');
      break;
      case 'added':
        DB::$instance->orderBy('created_at', 'DESC');
      break;
      default:
        DB::$instance->orderBy('"order"');
    }
    /** @var $appearances Appearance[] */
    $appearances = DB::$instance->where('guide', $guide_name)->get(Appearance::$table_name);

    $results = array_map(function (Appearance $a) use ($with_previews) {
      return self::mapAppearance($a, $with_previews, true);
    }, $appearances);
    Response::okCached([
      'appearances' => $results,
      'groups' => self::_fullListGroups($guide_name, $sort, $appearances),
    ], $cache_key, $cache_time);
  }

  /**
   * @param Appearance[] $appearances Already in the requested order
   *
   * @return array<int, array{name: string, appearanceIds: int[]}>
   */
  private static function _fullListGroups(string $guide, string $sort, array $appearances):array {
    switch ($sort) {
      case 'added':
        return [];
      case 'label':
        $groups = [];
        foreach ($appearances as $a) {
          $letter = strtoupper($a->label[0] ?? '#');
          $groups[preg_match('/^[A-Z]$/', $letter) ? $letter : '#'][] = (int)$a->id;
        }

        return array_map(fn($name, $ids) => ['name' => (string)$name, 'appearanceIds' => $ids], array_keys($groups), $groups);
      default:
        $sorted = Appearances::sort($appearances, $guide);
        $groups = [];
        foreach (CGUtils::GROUP_TAG_IDS_ASSOC[$guide] as $tag_id => $name) {
          if (!empty($sorted[$tag_id]))
            $groups[] = ['name' => $name, 'appearanceIds' => array_map(fn(Appearance $a) => (int)$a->id, $sorted[$tag_id])];
        }

        return $groups;
    }
  }

  private static function _resolveAppearance(array $params):Appearance {
    $id = (int)$params['id'];
    $appearance = Appearance::find($id);
    if (empty($appearance))
      Response::error(404, 'The appearance could not be found');
    return $appearance;
  }

  private static function _handlePrivateAppearanceCheck(Appearance $appearance):void {
    if ($appearance->private && Permission::insufficient('staff')){
      if (Auth::$signed_in && $appearance->owner_id === Auth::$user->id)
        return;

      // Usage with token is postponed for the rewrite

      Response::error(403, 'This appearance is private and you do not have permission to view it');
    }
  }

  static function mapPreviewAppearance(Appearance $a):array {
    return [
      'id' => $a->id,
      'label' => $a->label,
      'guide' => $a->guide,
      'ownerId' => $a->owner_id === null ? null : (int)$a->owner_id,
      'previewData' => array_map(fn(Color $c) => $c->hex, $a->getPreviewColors()),
    ];
  }

  function pinned() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $guide = $_GET['guide'] ?? null;
    if (!isset(CGUtils::GUIDE_MAP[$guide]))
      Response::invalid('guide', 'The selected guide is invalid.');

    $pins = PinnedAppearance::find('all', ['conditions' => ['guide = ?', $guide], 'order' => 'id asc']);
    Response::ok(array_map(fn(PinnedAppearance $p) => self::mapAppearance($p->appearance, false), $pins));
  }

  function locate(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $appearance = self::_resolveAppearance($params);

    self::_handlePrivateAppearanceCheck($appearance);

    Response::ok(self::mapPreviewAppearance($appearance));
  }

  function get(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $appearance = self::_resolveAppearance($params);

    self::_handlePrivateAppearanceCheck($appearance);

    Response::ok(self::mapAppearance($appearance, false) + [
      'canEdit' => $appearance->canBeManagedByVisitor(),
      'relatedAppearances' => array_values(array_map(
        fn($r) => self::mapPreviewAppearance($r->target),
        array_filter($appearance->related_appearances, fn($r) => $r->target !== null && (!$r->target->private || $r->target->canBeManagedByVisitor()))
      )),
      'relatedShows' => array_map(fn(Show $show) => ShowAPIController::mapShowListItem($show), $appearance->related_shows),
      'cutieMarks' => array_map(fn(Cutiemark $cm) => self::mapCutieMark($cm), $appearance->cutiemarks),
    ]);
  }

  function tagChanges(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();
    if (Permission::insufficient('staff'))
      Response::denied();

    $appearance = self::_resolveAppearance($params);
    if ($appearance->owner_id !== null)
      CoreUtils::notFound();

    [$page, $size] = self::_pageParams(25);

    $where = ['conditions' => ['appearance_id = ?', $appearance->id]];
    $total = TagChange::count($where);
    $changes = TagChange::find('all', $where + ['order' => 'created_at desc, id desc', 'limit' => $size, 'offset' => ($page - 1) * $size]);

    Response::ok([
      'changes' => array_map(fn(TagChange $c) => [
        'id' => $c->id,
        'tagId' => (int)$c->tag_id,
        'tagName' => $c->tag_name,
        'added' => (bool)$c->added,
        'user' => $c->user === null ? null : ['id' => $c->user->id, 'name' => $c->user->name],
        'createdAt' => gmdate('c', $c->created_at->getTimestamp()),
      ], $changes),
      'pagination' => [
        'currentPage' => $page,
        'totalPages' => max(1, (int)ceil($total / $size)),
        'totalItems' => $total,
        'itemsPerPage' => $size,
      ],
    ]);
  }

  /** @return int[] [page, size] */
  private static function _pageParams(int $defaultSize):array {
    $size = $_GET['size'] ?? $defaultSize;
    if (!is_numeric($size) || $size < 1 || $size > 100)
      Response::invalid('size', 'The size must be between 1 and 100.');
    $page = $_GET['page'] ?? 1;
    if (!is_numeric($page) || $page < 1)
      Response::invalid('page', 'The page must be at least 1.');

    return [(int)$page, (int)$size];
  }

  function palette(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $appearance = self::_resolveAppearance($params);
    self::_handlePrivateAppearanceCheck($appearance);

    $format = $_GET['format'] ?? null;
    if ($format === 'json')
      CGUtils::getSwatchesAI($appearance);
    if ($format === 'gpl')
      CGUtils::getSwatchesInkscape($appearance);

    Response::invalid('format', 'The format must be json or gpl.');
  }

  function image(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $appearance = self::_resolveAppearance($params);
    self::_handlePrivateAppearanceCheck($appearance);

    $type = $_GET['type'] ?? null;
    $format = $_GET['format'] ?? null;
    if (!in_array($type, ['palette', 'sprite', 'preview', 'facing'], true))
      Response::invalid('type', 'The type must be palette, sprite, preview or facing.');
    if (!in_array($format, ['png', 'svg'], true))
      Response::invalid('format', 'The format must be png or svg.');

    $self = $_SERVER['REQUEST_URI'];
    switch ("$type.$format"){
      case 'palette.png':
        CGUtils::renderAppearancePNG('', $appearance, $self);
      case 'sprite.png':
        if (!$appearance->hasSprite())
          CoreUtils::notFound();
        HTTP::tempRedirect($appearance->getSpriteURL());
      case 'sprite.svg':
        CGUtils::renderSpriteSVG('', $appearance, $self);
      case 'preview.svg':
        CGUtils::renderPreviewSVG($appearance, true, $self);
      case 'facing.svg':
        CGUtils::renderCMFacingSVG($appearance, $self);
    }

    Response::invalid('format', "A $type image is not available as $format.");
  }

  function cutieMarkDownload(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $appearance = self::_resolveAppearance($params);
    self::_handlePrivateAppearanceCheck($appearance);

    $cutiemark = Cutiemark::find((int)$params['cutieMarkId']);
    if (empty($cutiemark) || (int)$cutiemark->appearance_id !== $appearance->id || $appearance->hidden())
      Response::error(404, 'The cutie mark could not be found');

    $source = CoreUtils::truthy($_GET['source'] ?? false);
    if ($source && Permission::insufficient('staff'))
      Response::denied();

    $file = $source ? $cutiemark->getSourceFilePath() : $cutiemark->getRenderedFilePath();
    if (!$source && !file_exists($file))
      CGUtils::renderCMSVG($cutiemark, false);
    if (!file_exists($file))
      Response::error(404, 'The cutie mark has no file');

    $filename = $cutiemark->label === null
      ? CoreUtils::posess($appearance->label).' Cutie Mark'
      : $appearance->label.' - '.$cutiemark->label;

    CoreUtils::downloadFile($file, $filename.($source ? ' (source)' : '').'.svg');
  }

  static function mapCutieMark(Cutiemark $cm):array {
    $result = [
      'id' => $cm->id,
      'viewUrl' => $cm->getRenderedRelativeURL(),
      'facing' => $cm->facing,
      'favMe' => $cm->favme,
      'rotation' => (int)$cm->rotation,
    ];
    $contributor = $cm->contributor?->user;
    if ($contributor !== null)
      $result['contributor'] = UsersAPIController::mapUser($contributor);
    if (!empty($cm->label))
      $result['label'] = $cm->label;

    return $result;
  }

  function getColorGroups(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $appearance = self::_resolveAppearance($params);

    self::_handlePrivateAppearanceCheck($appearance);

    Response::ok(['colorGroups' => self::_getColorGroups($appearance)]);
  }

  function sprite(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $appearance = self::_resolveAppearance($params);

    self::_handlePrivateAppearanceCheck($appearance);

    CGUtils::renderSpritePNG($appearance, $_GET['size'] ?? null);
  }

  function preview(array $params) {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $appearance = self::_resolveAppearance($params);

    self::_handlePrivateAppearanceCheck($appearance);

    CGUtils::renderPreviewSVG($appearance, true, $_SERVER['REQUEST_URI']);
  }
}
