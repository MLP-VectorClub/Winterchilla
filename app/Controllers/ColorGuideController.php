<?php

namespace App\Controllers;

use App\Appearances;
use App\CGUtils;
use App\Controllers\Traits\ColorGuideAccessTrait;
use App\CoreUtils;
use App\DB;
use App\HTTP;
use App\Models\DeviantartUser;
use App\Models\MajorChange;
use App\Models\PinnedAppearance;
use App\Pagination;
use App\Permission;
use App\Regexes;
use App\Response;
use App\Time;
use App\UserPrefs;
use League\Uri\Components\Query;
use League\Uri\Modifier;
use League\Uri\Uri;

class ColorGuideController extends Controller {
  use ColorGuideAccessTrait;

  public function __construct() {
    parent::__construct();

    $this->_initAppearancePageState();
  }

  protected const GUIDE_MANAGE_JS = [
    'jquery.uploadzone',
    'pages/colorguide/tag-list',
    'pages/colorguide/manage',
  ];
  protected const GUIDE_MANAGE_CSS = [
    'pages/colorguide/manage',
  ];
  protected const GUIDE_MANAGE_LIBS = [
    'autocomplete',
    'sortable',
    'blob',
    'canvas-to-blob',
    'file-saver',
  ];

  protected static function _appendManageAssets(&$settings):void {
    $settings['js'] = array_merge($settings['js'] ?? [], self::GUIDE_MANAGE_JS);
    $settings['css'] = array_merge($settings['css'] ?? [], self::GUIDE_MANAGE_CSS);
    $settings['libs'] = isset($settings['libs']) ? array_merge($settings['libs'], self::GUIDE_MANAGE_LIBS) : self::GUIDE_MANAGE_LIBS;
  }

  public const FULL_LIST_ORDER = [
    'label' => 'alphabetically',
    'relevance' => 'by relevance',
    'added' => 'by date added',
  ];

  public function preferredGuide() {
    CGUtils::redirectToPreferredGuidePath();
  }

  public function fullList($params):void {
    $this->_initialize($params);

    $sort_by = $_GET['sort_by'] ?? null;
    if (!isset(self::FULL_LIST_ORDER[$sort_by]))
      $sort_by = 'relevance';
    switch ($sort_by){
      case 'label':
        DB::$instance->orderBy('label');
      break;
      case 'added';
        DB::$instance->orderBy('created_at', 'DESC');
      break;
    }
    $appearances = Appearances::get($this->guide, null, null, 'id,label,private,created_at');

    $path = Uri::new("{$this->path}/full");
    if ($sort_by !== 'relevance')
      $path = Modifier::wrap($path)->appendQuery(Query::fromVariable(['sort_by'=>$sort_by]))->unwrap();

    if (CoreUtils::isJSONExpected())
      Response::done([
        'html' => CGUtils::getFullListHTML($appearances, $sort_by, $this->guide, NOWRAP),
        'stateUrl' => (string)$path,
      ]);

    CoreUtils::fixPath($path);

    $is_staff = Permission::sufficient('staff');

    $libs = [];
    if ($is_staff)
      $libs[] = 'sortable';

    $json_export_url = CoreUtils::cachedAssetLink('mlpvc-colorguide', 'dist', 'json');
    $json_export_time = Time::tag((int)explode('?', $json_export_url)[1]);

    $import = [
      'guide' => $this->guide,
      'appearances' => $appearances,
      'sort_by' => $sort_by,
      'is_staff' => $is_staff,
      'full_list' => CGUtils::getFullListHTML($appearances, $sort_by, $this->guide),
      'json_export_url' => $json_export_url,
      'json_export_time' => $json_export_time,
    ];
    if ($is_staff){
      $import['max_upload_size'] = CoreUtils::getMaxUploadSize();
      $import['hex_color_pattern'] = Regexes::$hex_color;
    }
    CoreUtils::loadPage(__METHOD__, [
      'title' => 'Full List - '.CGUtils::GUIDE_MAP[$this->guide].' Color Guide',
      'css' => [true],
      'libs' => $libs,
      'js' => [true],
      'import' => $import,
    ]);
  }

  public function changeList($params):void {
    $this->_initialize($params);
    $pagination = new Pagination("{$this->path}/changes", 9, MajorChange::total($this->guide));

    CoreUtils::fixPath($pagination->toURI());
    $heading = 'Major '.CGUtils::GUIDE_MAP[$this->guide].' Color Changes';
    $title = "Page {$pagination->getPage()} - $heading - Color Guide";

    $changes = MajorChange::get(null, $this->guide, $pagination->getLimitString());

    CoreUtils::loadPage(__METHOD__, [
      'title' => $title,
      'heading' => $heading,
      'css' => [true],
      'js' => ['paginate'],
      'import' => [
        'guide' => $this->guide,
        'changes' => $changes,
        'pagination' => $pagination,
      ],
    ]);
  }

  public function index():void {
    $title = 'Color Guide List';
    $subheading = 'List of all color guides maintained by the club staff';
    $guide_counts_raw = DB::$instance->query('SELECT guide, count(guide) as count FROM appearances GROUP BY guide');
    $guide_counts = [];
    foreach ($guide_counts_raw as $item)
      $guide_counts[$item['guide']] = $item['count'];

    $json_export_url = CoreUtils::cachedAssetLink('mlpvc-colorguide', 'dist', 'json');
    $json_export_time = Time::tag((int)explode('?', $json_export_url)[1]);
    $settings = [
      'title' => $title,
      'heading' => $title,
      'css' => [true],
      'og' => [
        'title' => $title,
        'description' => $subheading,
      ],
      'import' => [
        'guides' => CGUtils::GUIDE_MAP,
        'guide_counts' => $guide_counts,
        'subheading' => $subheading,
        'json_export_url' => $json_export_url,
        'json_export_time' => $json_export_time,
      ],
    ];
    if (Permission::sufficient('staff')){
      self::_appendManageAssets($settings);
    }
    CoreUtils::loadPage(__METHOD__, $settings);
  }

  public function guide($params):void {
    $this->_initialize($params);

    $title = '';
    /** @var $appearances_per_page int */
    $appearances_per_page = UserPrefs::get('cg_itemsperpage');
    $elastic_avail = CGUtils::isElasticAvailable();
    $searching = !empty($_GET['q']) && CoreUtils::trim($_GET['q']) !== '';
    $json_response = CoreUtils::isJSONExpected();
    if ($elastic_avail){
      $pagination = new Pagination($this->path, $appearances_per_page);
      [$appearances, $search_query] = CGUtils::searchGuide($pagination, $this->guide, $searching, $title);
    }
    else {
      if ($searching && $json_response)
        Response::fail('The ElasticSearch server is currently down and search is not available, sorry for the inconvenience.<br>Please <a class="send-feedback">let us know</a> about this issue.', ['unavail' => true]);

      $search_query = null;
      $entry_count = DB::$instance->where('guide', $this->guide)->where('id != 0')->count('appearances');

      $pagination = new Pagination($this->path, $appearances_per_page, $entry_count);
      $appearances = Appearances::get($this->guide, $pagination->getLimit());
    }

    if (isset($_REQUEST['btnl'])){
      $found = !empty($appearances[0]->id);
      if (CoreUtils::isJSONExpected()){
        if (!$found)
          Response::fail('Your search returned no results.');
        Response::done(['goto' => $appearances[0]->toURL()]);
      }
      if ($found)
        HTTP::tempRedirect($appearances[0]->toURL());
    }

    $path = $pagination->toURI();
    $remove_params = null;
    if (!empty($search_query))
      $path = Modifier::wrap($path)->appendQuery(Query::fromVariable(['q' => $search_query]))->unwrap();
    else $remove_params = ['q'];
    CoreUtils::fixPath($path, $remove_params);
    $heading = CGUtils::GUIDE_MAP[$this->guide].' Color Guide';
    $title .= "Page {$pagination->getPage()} - $heading";

    if (!file_exists(CGUtils::GUIDE_EXPORT_PATH))
      CGUtils::saveExportData();

    $json_export_url = CoreUtils::cachedAssetLink('mlpvc-colorguide', 'dist', 'json');
    $json_export_time = Time::tag((int)explode('?', $json_export_url)[1]);

    $settings = [
      'title' => $title,
      'heading' => $heading,
      'noindex' => $searching,
      'css' => [true],
      'js' => ['jquery.ctxmenu', true, 'paginate'],
      'libs' => ['autocomplete'],
      'import' => [
        'guide' => $this->guide,
        'appearances' => $appearances,
        'pagination' => $pagination,
        'elastic_avail' => $elastic_avail,
        'pinned_appearances' => PinnedAppearance::getGuideAppearances($this->guide),
        'search_query' => $search_query ?? null,
        'json_export_url' => $json_export_url,
        'json_export_time' => $json_export_time,
      ],
    ];
    if (Permission::sufficient('staff')){
      self::_appendManageAssets($settings);
      $settings['import']['max_upload_size'] = CoreUtils::getMaxUploadSize();
      $settings['import']['hex_color_regex'] = Regexes::$hex_color;
    }
    CoreUtils::loadPage(__METHOD__, $settings);
  }

  public function blending():void {
    CoreUtils::fixPath('/cg/blending');

    $hex_pattern = preg_replace('~^/(.*)/.*$~', '$1', Regexes::$hex_color->jsExport());
    $dasprid = DeviantartUser::find_by_name('dasprid');
    $dasprid_link = empty($dasprid)
      ? "<a href='https://www.deviantart.com/dasprid'>dasprid</a>"
      : $dasprid->user->toAnchor(WITH_AVATAR);
    CoreUtils::loadPage(__METHOD__, [
      'title' => 'Color Blending Calculator',
      'css' => [true],
      'js' => [true],
      'import' => [
        'hex_pattern' => $hex_pattern,
        'nav_blending' => true,
        'dasprid_link' => $dasprid_link,
        'hex_color_regex' => Regexes::$hex_color,
      ],
    ]);
  }

  public function blendingReverse():void {
    CoreUtils::fixPath('/cg/blending-reverse');

    CoreUtils::loadPage(__METHOD__, [
      'title' => 'Blending Reverser',
      'libs' => [
        'no-ui-slider',
        'blob',
        'canvas-to-blob',
        'file-saver',
      ],
      'css' => [true],
      'js' => [true],
      'import' => [
        'nav_blendingrev' => true,
        'hex_color_regex' => Regexes::$hex_color,
      ],
    ]);
  }

  public function picker():void {
    CoreUtils::loadPage(__METHOD__, [
      'title' => 'Color Picker',
      'view' => [true],
      'css' => [true],
      'import' => ['nav_picker' => true],
    ]);
  }

  public function pickerFrame():void {
    CoreUtils::loadPage(__METHOD__, [
      'noindex' => true,
      'title' => 'Color Picker',
      'libs' => [
        'jquery',
        'ba-throttle-debounce',
        'md5',
        'dragscroll',
        'no-ui-slider',
        'paste',
        'cuid',
        'font-awesome',
      ],
      'css' => [true],
      'default-js' => false,
      'default-libs' => false,
      'js' => [
        'shared-utils',
        'dialog',
        'lib/canvas.hdr',
        true,
      ],
    ]);
  }
}
