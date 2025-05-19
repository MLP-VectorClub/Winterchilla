<?php

namespace App\Models;

use ActiveRecord\DatabaseException;
use ActiveRecord\DateTime;
use App\Appearances;
use App\Auth;
use App\CGUtils;
use App\CoreUtils;
use App\DB;
use App\JSON;
use App\Permission;
use App\Response;
use App\ShowHelper;
use App\Tags;
use App\Time;
use App\Twig;
use App\UserPrefs;
use App\Users;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception as ElasticMissing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException as ElasticNoNodesAvailableException;
use Elasticsearch\Common\Exceptions\ServerErrorResponseException as ElasticServerErrorResponseException;
use Exception;
use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;
use RuntimeException;
use SeinopSys\RGBAColor;
use function count;
use function in_array;

/**
 * @property int                 $id
 * @property int                 $order
 * @property string              $label
 * @property string              $notes_src
 * @property string              $notes_rend
 * @property int|null            $owner_id
 * @property DateTime            $created_at
 * @property DateTime            $updated_at
 * @property DateTime            $last_cleared
 * @property string              $guide
 * @property bool                $private
 * @property string              $token
 * @property string              $sprite_hash
 * @property Cutiemark[]         $cutiemarks          (Via relations)
 * @property ColorGroup[]        $color_groups        (Via relations)
 * @property User|null           $owner               (Via relations)
 * @property RelatedAppearance[] $related_appearances (Via relations)
 * @property Tag[]               $tags                (Via relations)
 * @property Tagged[]            $tagged              (Via relations)
 * @property MajorChange[]       $major_changes       (Via relations)
 * @property ShowAppearance[]    $show_appearances    (Via relations)
 * @property Show[]              $related_shows       (Via relations)
 * @property bool                $pinned              (Via magic method)
 * @method static Appearance|Appearance[] find(...$args)
 * @method static Appearance[] all(...$args)
 */
class Appearance extends NSModel implements Linkable {
  public static $table_name = 'appearances';

  public static $has_many = [
    ['cutiemarks', 'foreign_key' => 'appearance_id', 'order' => 'facing asc'],
    ['tags', 'through' => 'tagged'],
    ['tagged', 'class' => 'Tagged'],
    ['color_groups', 'order' => '"order" asc, id asc'],
    ['related_appearances', 'class' => 'RelatedAppearance', 'foreign_key' => 'source_id', 'order' => 'target_id asc'],
    ['major_changes', 'class' => 'MajorChange', 'order' => 'id desc'],
    ['show_appearances'],
    ['related_shows', 'class' => 'Show', 'through' => 'show_appearances'],
  ];

  /**
   * For Twig
   *
   * @return RelatedAppearance[]
   */
  public function getRelated_appearances() {
    return $this->related_appearances;
  }

  public static $belongs_to = [
    ['owner', 'class' => 'User', 'foreign_key' => 'owner_id'],
  ];

  /** For Twig */
  public function getOwner() {
    return $this->owner;
  }

  public static $before_save = ['render_notes'];
  public static $after_destroy = ['clearIndex', 'clearRenderedImages', 'deleteSprite'];

  /**
   * Ensure that this is only called after user is authenticated as we would leak colors otherwise
   *
   * @return Color[]
   */
  public function getPreviewColors() {
    /** @var $arr Color[] */
    $arr = DB::$instance->setModel(Color::class)->query(
      'SELECT c.hex FROM colors c
			LEFT JOIN color_groups cg ON c.group_id = cg.id
			WHERE cg.appearance_id = ? AND c.hex IS NOT NULL
			ORDER BY cg."order", c."order"
			LIMIT 4', [$this->id]);

    if (!empty($arr))
      usort($arr, function (Color $a, Color $b) {
        /** @noinspection NullPointerExceptionInspection */
        return RGBAColor::parse($b->hex)->yiq() <=> RGBAColor::parse($a->hex)->yiq();
      });

    return $arr;
  }

  public function get_pinned():bool {
    return $this->guide !== null && PinnedAppearance::existsForAppearance($this->id);
  }

  public function get_notes_rend():?string {
    if (empty($this->notes_src))
      return $this->notes_src;

    if ($this->read_attribute('notes_rend') === null){
      $this->notes_rend = $this->processNotes();
      $this->save();
    }

    return $this->read_attribute('notes_rend');
  }

  public function getPaletteFilePath() {
    return FSPATH."cg_render/appearance/{$this->id}/palette.png";
  }

  /**
   * Get rendered PNG URL
   *
   * @return string
   */
  public function getPaletteURL():string {
    $pcg_prefix = $this->owner_id !== null ? $this->owner->toURL() : '';
    $palette_path = $this->getPaletteFilePath();
    $file_mod = CoreUtils::filemtime($palette_path);
    $token = !empty($_GET['token']) ? '&token='.urlencode($_GET['token']) : '';

    return "$pcg_prefix/cg/v/{$this->id}p.png?t=$file_mod$token";
  }

  /**
   * Replaces non-alphanumeric characters in the appearance label with dashes
   *
   * @return string
   */
  public function getURLSafeLabel():string {
    return CoreUtils::makeUrlSafe($this->label);
  }

  public static function find_dupe(bool $creating, array $data) {
    $firstcol = $data['guide'] === null ? 'owner_id' : 'guide';
    $conds = [
      "$firstcol = ? AND label = ?",
      $data[$firstcol],
      $data['label'],
    ];
    if (!$creating){
      $conds[0] .= ' AND id != ?';
      $conds[] = $data['id'];
    }

    return Appearance::find('first', ['conditions' => $conds]);
  }

  public const SPRITE_SIZES = [
    'REGULAR' => 600,
    'SOURCE' => 300,
  ];

  /**
   * Get sprite URL
   *
   * @param int    $size
   * @param string $fallback
   *
   * @return string
   */
  public function getSpriteURL(?int $size = null, string $fallback = ''):string {
    if ($this->hasSprite()){
      $sprite_hash = $this->sprite_hash ?? $this->regenerateSpriteHash();
      $url = Uri::createFromString(PUBLIC_API_V0_PATH."/appearances/{$this->id}/sprite");
      $query_params = [];
      if (!empty($sprite_hash))
        $query_params['hash'] = $sprite_hash;
      if ($size !== null)
        $query_params['size'] = $size;
      if (!empty($_GET['token']))
        $query_params['token'] = $_GET['token'];

      if (!empty($query_params))
        $url = UriModifier::appendQuery($url, Query::createFromParams($query_params));

      return (string)$url;
    }

    return $fallback;
  }

  public function getStaticSpriteURL() {
    $url = Uri::createFromString("/img/sprites/{$this->id}.png");
    $sprite_hash = $this->sprite_hash ?? $this->regenerateSpriteHash();
    if (!empty($sprite_hash))
      $url = UriModifier::appendQuery($url, Query::createFromParams(['hash' => $sprite_hash]));

    return (string)$url;
  }

  /**
   * @return boolean
   */
  public function hasSprite():bool {
    return file_exists($this->getSpriteFilePath());
  }

  /**
   * @return string|null
   */
  public function regenerateSpriteHash():?string {
    $sprite_path = $this->getSpriteFilePath();
    if (!file_exists($sprite_path)){
      return null;
    }

    $this->sprite_hash = CoreUtils::generateFileHash($sprite_path);

    return $this->sprite_hash;
  }

  /**
   * Returns the HTML for sprite images
   * USED IN TWIG - DO NOT REMOVE
   *
   * @param bool $canUpload
   * @param User $user
   *
   * @return string
   * @noinspection PhpUnused
   */
  public function getSpriteHTML(bool $canUpload, ?User $user = null):string {
    if (Auth::$signed_in && $this->owner_id === Auth::$user->id && !UserPrefs::get('a_pcgsprite', $user))
      $canUpload = false;

    $imgPth = $this->getSpriteURL();
    if (!empty($imgPth)){
      $img = "<a href='$imgPth' target='_blank' title='Open image in new tab'><img src='$imgPth' alt='".CoreUtils::aposEncode($this->label)."'></a>";
      if ($canUpload)
        $img = "<div class='upload-wrap'>$img</div>";
    }
    else if ($canUpload)
      $img = "<div class='upload-wrap'><a><img src='/img/blank-pixel.png' alt='blank pixel'></a></div>";
    else return '';

    return "<div class='sprite'>$img</div>";
  }

  private function processNotes():string {
    $notes_rend = CoreUtils::sanitizeHtml($this->notes_src);
    $notes_rend = preg_replace('/(\s)(&gt;&gt;(\d+))(\D|$)/', "$1<a href='https://derpibooru.org/$3'>$2</a>$4", $notes_rend);
    if ($this->guide === CGUtils::GUIDE_FIM){
      $notes_rend = preg_replace_callback('/'.EPISODE_ID_PATTERN.'/', function ($a) {
        $episode = ShowHelper::getActual((int)$a[1], (int)$a[2]);

        return !empty($episode)
          ? "<a href='{$episode->toURL()}'>".CoreUtils::aposEncode($episode->formatTitle(AS_ARRAY, 'title')).'</a>'
          : "<strong>{$a[0]}</strong>";
      }, $notes_rend);
    }
    $notes_rend = preg_replace_callback('/'.MOVIE_ID_PATTERN.'/', function ($a) {
      $show = Show::find((int)$a[1]);

      return !empty($show)
        ? "<a href='{$show->toURL()}'>".CoreUtils::aposEncode(ShowHelper::shortenTitlePrefix($show->formatTitle(AS_ARRAY, 'title'))).'</a>'
        : "<strong>{$a[0]}</strong>";
    }, $notes_rend);
    $notes_rend = preg_replace_callback('/(^|\s(?!\\\\))#(\d+)(\'s?)?\b/', function ($a) {

      $appearance = DB::$instance->where('id', $a[2])->getOne('appearances');

      return (
      !empty($appearance)
        ? "{$a[1]}<a href='/cg/v/{$appearance->id}'>{$appearance->label}</a>".(!empty($a[3]) ? CoreUtils::posess($appearance->label, true) : '')
        : (string)$a[0]
      );
    }, $notes_rend);

    return str_replace('\#', '#', $notes_rend);
  }

  /**
   * Get the notes for a specific appearance
   *
   * @param bool $wrap
   * @param bool $cm_link
   *
   * @return string
   */
  public function getNotesHTML(bool $wrap = WRAP, bool $cm_link = true):string {
    if (!empty($this->notes_src)){
      $notes = "<span class='notes-text'>{$this->notes_rend}</span>";
    }
    else $notes = '';
    $cm_count = Cutiemark::count(['appearance_id' => $this->id]);

    return Twig::$env->render('appearances/_notes.html.twig', [
      'notes' => $notes,
      'cm_count' => $cm_count,
      'wrap' => $wrap,
      'cm_link' => $cm_link,
    ]);
  }

  /**
   * Returns the markup of the color list for a specific appearance
   *
   * @return string
   */
  public function getColorsHTML(bool $compact = true, bool $is_owner = false, bool $wrap = WRAP):string {
    if ($placeholder = $this->getPendingPlaceholder())
      return $placeholder;

    return Twig::$env->render('appearances/_colors.html.twig', [
      'color_groups' => $this->color_groups,
      'all_colors' => CGUtils::getColorsForEach($this->color_groups),
      'compact' => $compact,
      'is_owner' => $is_owner,
      'wrap' => $wrap,
    ]);
  }

  /**
   * Return the markup of a set of tags belonging to a specific appearance
   *
   * @param bool $wrap
   *
   * @return string
   */
  public function getTagsHTML(bool $wrap = WRAP):string {
    $tags = Tags::getFor($this->id, null, Permission::sufficient('staff'));

    $HTML = '';
    if (!empty($tags)) foreach ($tags as $t)
      $HTML .= $t->getHTML($this->guide);

    return $wrap ? "<div class='tags'>$HTML</div>" : $HTML;
  }

  /**
   * Returns the markup for the time of last update displayed under an appaerance
   *
   * @param bool $wrap
   *
   * @return string
   */
  public function getUpdatesHTML($wrap = WRAP) {
    $update = MajorChange::get($this->id, null, MOST_RECENT);
    if (!empty($update)){
      $update = 'Last major change '.Time::tag($update->created_at);
    }
    else {
      if (Permission::insufficient('staff'))
        return '';
      $update = '';
    }

    return $wrap ? "<div class='update'>$update</div>" : $update;
  }

  public function getChangesHTML(bool $wrap = WRAP):string {
    $HTML = '';
    if (count($this->major_changes) === 0)
      return $HTML;

    $isStaff = Permission::sufficient('staff');
    foreach ($this->major_changes as $change){
      $li = CoreUtils::escapeHTML($change->reason).' &ndash; '.Time::tag($change->created_at);
      if ($isStaff)
        $li .= ' by '.$change->user->toAnchor();
      $HTML .= "<li>$li</li>";
    }
    if (!$wrap)
      return $HTML;

    return <<<HTML
			<section class="major-changes">
				<h2><span class='typcn typcn-warning'></span>List of major changes</h2>
				<ul>$HTML</ul>
			</section>
			HTML;
  }

  /**
   * Returns the HTML of the "Featured in" section of appearance pages
   *
   * @return string
   * @throws Exception
   */
  public function getRelatedShowsHTML():string {
    $related_shows = $this->related_shows;
    $is_staff = Permission::sufficient('staff');
    if (empty($related_shows) && !$is_staff)
      return '';

    return Twig::$env->render('appearances/_featured_in.html.twig', [
      'related_shows' => $related_shows,
    ]);
  }

  public function verifyToken(?string $token = null) {
    if ($token === null){
      if (!isset($_GET['token']) || !is_string($_GET['token']))
        return false;
      /** @noinspection CallableParameterUseCaseInTypeContextInspection */
      $token = $_GET['token'];
    }

    return hash_equals($this->token, $token);
  }

  public function isPrivate(bool $ignoreStaff = false):bool {
    $isPrivate = !empty($this->private);
    if (
      !$ignoreStaff && (
        Permission::sufficient('staff')
        || (Auth::$signed_in ? $this->owner_id === Auth::$user->id : false)
        || ($this->owner_id !== null && $this->verifyToken())
      )
    )
      $isPrivate = false;

    return $isPrivate;
  }

  /**
   * Returns the HTML for the placeholder which is displayed in place of the color group
   *  to anyone without edit access while the appearance is private
   *
   * @return string
   */
  public function getPendingPlaceholder():string {
    return $this->isPrivate() ? "<div class='colors-pending'><span class='typcn typcn-time'></span> ".($this->last_cleared !== null
        ? 'This appearance is currently undergoing maintenance and will be available again shortly &mdash; '.Time::tag($this->last_cleared)
        : 'This appearance will be finished soon, please check back later &mdash; '.Time::tag($this->created_at)).'</div>' : '';
  }

  /**
   * Retruns preview image link
   *
   * @return string
   * @see CGUtils::renderPreviewSVG()
   */
  public function getPreviewURL():string {
    $colors = CGUtils::hexesToFilename(CGUtils::colorsToHexes($this->getPreviewColors()));
    $path = str_replace('#', $colors, CGUtils::PREVIEW_SVG_PATH);
    if (!file_exists($path))
      CGUtils::renderPreviewSVG($this, false);
    $relative_path = str_replace(FSPATH, '/img/', $path);

    return "$relative_path?t=".CoreUtils::filemtime($path);
  }

  public function getPreviewHTML():string {
    $locked = $this->owner_id !== null && $this->private;

    if ($this->isPrivate(true))
      $preview = "<span class='typcn typcn-".($locked ? 'lock-closed' : 'time').' color-'.($locked ? 'orange' : 'darkblue')."'></span> ";
    else {
      $preview = $this->getPreviewURL();
      $preview = "<img src='$preview' class='preview' alt=''>";
    }

    return $preview;
  }

  /**
   * @param string $facing
   * @param bool   $ts
   *
   * @return string
   * @see CGUtils::renderCMFacingSVG()
   */
  public function getFacingSVGURL(?string $facing = null, bool $ts = true) {
    if ($facing === null)
      $facing = 'left';
    $path = str_replace(['#', '@'], [$this->id, $facing], CGUtils::CMDIR_SVG_PATH);

    return "/cg/v/{$this->id}f.svg?facing=$facing".
      ($ts ? '&t='.CoreUtils::filemtime($path) : '').
      (!empty($_GET['token']) ? "&token={$_GET['token']}" : '');
  }

  /**
   * Get a link to this appearance
   * Because redirects take care of setting & enforcing the guide
   * and owner data in the URL we can skip that for short sharing links.
   *
   * @param bool $sharing
   *
   * @return string
   */
  public function toURL($sharing = false):string {
    $safe_label = $this->getURLSafeLabel();
    $pcg = $this->owner_id !== null;
    $owner = !$pcg || $sharing ? '' : $this->owner->toURL();
    $guide = $pcg || $sharing ? '' : "{$this->guide}/";

    return "$owner/cg/{$guide}v/{$this->id}-$safe_label";
  }

  public function toAnchor():string {
    return "<a href='{$this->toURL()}'>{$this->getBabelLabel()}</a>";
  }

  public function toAnchorWithPreview():string {
    return "<a href='{$this->toURL()}'>{$this->getPreviewHTML()}<span>{$this->getBabelLabel()}</span></a>";
  }

  /**
   * @param Tag $tag
   *
   * @return bool Indicates whether the passed tag is used on the appearance
   */
  public function is_tagged(Tag $tag):bool {
    return Tagged::is($tag, $this);
  }

  public function getRelatedHTML():string {
    return Twig::$env->render('appearances/_related.html.twig', [
      'appearance' => $this,
    ]);
  }

  /**
   * Re-index the appearance in ElasticSearch
   */
  public function reindex() {
    // We don't want to index pcg appearances
    if ($this->owner_id !== null)
      return;

    if (!$this->pinned){
      $this->updateIndex();
    }
  }

  public function updateIndex() {
    if ($this->pinned) {
      $this->clearIndex();
      return;
    }

    try {
      CoreUtils::elasticClient()->update($this->toElasticArray(false, true));
    }
    catch (ElasticNoNodesAvailableException | ElasticServerErrorResponseException $e){
      CoreUtils::logError("ElasticSearch server was down when server attempted to index appearance {$this->id}");
    }
    catch (ElasticMissing404Exception $e){
      CoreUtils::elasticClient()->update($this->toElasticArray(false));
    }
  }

  public function getElasticMeta() {
    return array_merge(CGUtils::ELASTIC_BASE, [
      'id' => $this->id,
    ]);
  }

  public function getElasticBody() {
    if ($this->owner_id !== null)
      throw new RuntimeException('Attempt to get ElasticSearch body for private appearance');

    $tags = Tags::getFor($this->id, null, true, true);
    $tag_names = [];
    $tag_ids = [];
    foreach ($tags as $k => $tag){
      $tag_names[] = $tag->name;
      $tag_ids[] = $tag->id;
    }
    $synonym_tags = Tag::synonyms_of($tag_ids);
    foreach ($synonym_tags as $tag)
      $tag_names[] = $tag->name;

    return [
      'label' => $this->label,
      'order' => $this->order,
      'private' => $this->private,
      'guide' => $this->guide,
      'tags' => $tag_names,
    ];
  }

  public function toElasticArray(bool $no_body = false, bool $update = false):array {
    $params = $this->getElasticMeta();
    if ($no_body)
      return $params;
    $params['body'] = $this->getElasticBody();
    if ($update)
      $params['body'] = [
        'doc' => $params['body'],
        'upsert' => $params['body'],
      ];

    return $params;
  }

  public function clearRelations() {
    RelatedAppearance::delete_all(['conditions' => [
      'source_id = :id OR target_id = :id',
      ['id' => $this->id],
    ]]);
  }

  public const STATIC_RELEVANT_COLORS = [
    [
      'hex' => '#D8D8D8',
      'label' => 'Mannequin | Outline',
      'mandatory' => false,
    ],
    [
      'hex' => '#E6E6E6',
      'label' => 'Mannequin | Fill',
      'mandatory' => false,
    ],
    [
      'hex' => '#BFBFBF',
      'label' => 'Mannequin | Shadow Outline',
      'mandatory' => false,
    ],
    [
      'hex' => '#CCCCCC',
      'label' => 'Mannequin | Shdow Fill',
      'mandatory' => false,
    ],
  ];

  /**
   * @param bool $treatHexNullAsEmpty
   *
   * @return bool
   */
  public function hasColors(bool $treatHexNullAsEmpty = false):bool {
    $hexnull = $treatHexNullAsEmpty ? ' AND hex IS NOT NULL' : '';

    return (DB::$instance->querySingle(
          "SELECT count(*) as cnt FROM colors
			WHERE group_id IN (SELECT group_id FROM color_groups WHERE appearance_id = ?)$hexnull", [$this->id])['cnt'] ?? 0) > 0;
  }

  public const
    PONY_TEMPLATE = [
    'Coat' => [
      'Outline',
      'Fill',
      'Shadow Outline',
      'Shadow Fill',
    ],
    'Mane & Tail' => [
      'Outline',
      'Fill',
    ],
    'Iris' => [
      'Gradient Top',
      'Gradient Middle',
      'Gradient Bottom',
      'Highlight Top',
      'Highlight Bottom',
    ],
    'Cutie Mark' => [
      'Fill 1',
      'Fill 2',
    ],
    'Magic' => [
      'Aura',
    ],
  ],
    HUMAN_TEMPLATE = [
    'Skin' => [
      'Outline',
      'Fill',
    ],
    'Hair' => [
      'Outline',
      'Fill',
    ],
    'Eyes' => [
      'Gradient Top',
      'Gradient Middle',
      'Gradient Bottom',
      'Highlight Top',
      'Highlight Bottom',
      'Eyebrows',
    ],
  ];

  /**
   * Apply pre-defined template to an appearance
   *
   * @return self
   */
  public function applyTemplate():self {
    if (ColorGroup::exists(['conditions' => ['appearance_id = ?', $this->id]]))
      throw new RuntimeException('Template can only be applied to empty appearances');

    $scheme = $this->guide === CGUtils::GUIDE_EQG
      ? self::HUMAN_TEMPLATE
      : self::PONY_TEMPLATE;

    $cgi = 1;
    foreach ($scheme as $group_name => $color_names){
      /** @var $Group ColorGroup */
      $Group = ColorGroup::create([
        'appearance_id' => $this->id,
        'label' => $group_name,
        'order' => $cgi++,
      ]);
      $GroupID = $Group->id;
      if (!$GroupID)
        throw new RuntimeException(rtrim(sprintf("Color group \"%s\" could not be created: %s", $group_name, DB::$instance->getLastError()), ': '));

      $ci = 1;
      foreach ($color_names as $label){
        $new_color = new Color([
          'group_id' => $GroupID,
          'label' => $label,
          'order' => $ci++,
        ]);
        if (!$new_color->save())
          throw new RuntimeException(rtrim(sprintf("Color \"%s\" could not be added: %s", $label, DB::$instance->getLastError()), ': '));
      }
    }

    return $this;
  }

  public const CLEAR_ALL = [
    self::CLEAR_PALETTE,
    self::CLEAR_PREVIEW,
    self::CLEAR_CM,
    self::CLEAR_CMDIR,
    self::CLEAR_SPRITE,
    self::CLEAR_SPRITE_600,
    self::CLEAR_SPRITE_SVG,
    self::CLEAR_SPRITE_PREVIEW,
  ];
  public const
    CLEAR_PALETTE = 'palette.png',
    CLEAR_PREVIEW = 'preview.svg',
    CLEAR_CM = '&cutiemark',
    CLEAR_CMDIR = 'cmdir-*.svg',
    CLEAR_SPRITE = 'sprite.png',
    CLEAR_SPRITE_600 = 'sprite-600.png',
    CLEAR_SPRITE_SVG = 'sprite.svg',
    CLEAR_SPRITE_PREVIEW = 'sprite-preview.png.txt',
    CLEAR_SPRITE_MAP = 'linedata.json.gz';

  /**
   * Deletes rendered images of an appearance (forcing its re-generation)
   *
   * @param array $which
   *
   * @return bool
   */
  public function clearRenderedImages(array $which = self::CLEAR_ALL):bool {
    $success = [];
    $clear_cm_pos = array_search(self::CLEAR_CM, $which, true);
    if ($clear_cm_pos !== false){
      array_splice($which, $clear_cm_pos, 1);
      foreach ($this->cutiemarks as $cm){
        $fpath = $cm->getRenderedFilePath();
        $success[] = CoreUtils::deleteFile($fpath);
      }
    }
    foreach ($which as $suffix){
      $path = FSPATH."cg_render/appearance/{$this->id}/$suffix";
      if (!CoreUtils::contains($path, '*'))
        $success[] = CoreUtils::deleteFile($path);
      else {
        foreach (glob($path) as $file)
          $success[] = CoreUtils::deleteFile($file);
      }
    }

    return !in_array(false, $success, true);
  }

  public const DEFAULT_COLOR_MAPPING = [
    'Coat Outline' => '#0D0D0D',
    'Coat Shadow Outline' => '#000000',
    'Coat Fill' => '#2B2B2B',
    'Coat Shadow Fill' => '#171717',
    'Mane & Tail Outline' => '#333333',
    'Mane & Tail Fill' => '#5E5E5E',
  ];

  public function getColorMapping($DefaultColorMapping) {
    $colors = DB::$instance->query(
      'SELECT cg.label as cglabel, c.label as clabel, c.hex
			FROM color_groups cg
			LEFT JOIN colors c on c.group_id = cg.id
			WHERE cg.appearance_id = ?
			ORDER BY cg.order, c.label', [$this->id]);

    $color_mapping = [];
    foreach ($colors as $row){
      $cglabel = preg_replace('/^(Costume|Dress)$/', 'Coat', $row['cglabel']);
      $cglabel = preg_replace('/^(Coat|Mane & Tail) \([^)]+\)$/', '$1', $cglabel);
      $eye = $row['cglabel'] === 'Iris';
      $eye_regex = !$eye ? '|Gradient(?:\s(?:Light|(?:\d+\s)?(?:Top|Botom)))?\s' : '';
      $colorlabel = preg_replace("~^(?:(?:(?:Purple|Yellow|Red)\\s)?(?:Main|First|Normal{$eye_regex}))?(.+?)(?:\\s\\d+)?(?:/.*)?\$~", '$1', $row['clabel']);
      $label = "$cglabel $colorlabel";
      if (isset($DefaultColorMapping[$label]) && !isset($color_mapping[$label]))
        $color_mapping[$label] = $row['hex'];
    }
    if (!isset($color_mapping['Coat Shadow Outline']) && isset($color_mapping['Coat Outline']))
      $color_mapping['Coat Shadow Outline'] = $color_mapping['Coat Outline'];
    if (!isset($color_mapping['Coat Shadow Fill']) && isset($color_mapping['Coat Fill']))
      $color_mapping['Coat Shadow Fill'] = $color_mapping['Coat Fill'];

    return $color_mapping;
  }

  public function render_notes() {
    if ($this->notes_src === null)
      $this->notes_rend = null;
    else $this->notes_rend = $this->processNotes();
  }

  public function clearIndex() {
    if ($this->owner_id === null){
      try {
        CoreUtils::elasticClient()->delete($this->toElasticArray(true));
      }
      catch (Missing404Exception $e){
        $message = JSON::decode($e->getMessage());

        // Eat error if appearance was not indexed
        if (!isset($message['found']) || $message['found'] !== false)
          throw $e;
      }
      catch (NoNodesAvailableException $e){
        CoreUtils::logError("ElasticSearch server was down when server attempted to remove appearance {$this->id}");
      }
    }
  }

  public function hidden(bool $ignoreStaff = false):bool {
    return $this->owner_id !== null && $this->private && $this->isPrivate($ignoreStaff);
  }

  public function getTagsAsText(?bool $synonyms = null, string $separator = ', ') {
    if ($synonyms === null)
      $synonyms = Permission::sufficient('staff');
    $tags = Tags::getFor($this->id, null, $synonyms);

    return Tags::getList($tags, $separator);
  }

  public function processTagChanges(string $old_tags, string $new_tags, ?string $guide) {
    $old = array_map([CoreUtils::class, 'trim'], explode(',', $old_tags));
    $new = array_map([CoreUtils::class, 'trim'], explode(',', $new_tags));
    $added_tag_names = array_diff($new, $old);
    $removed_tag_names = array_diff($old, $new);

    if (!empty($removed_tag_names)){
      $removed_tags = DB::$instance->disableAutoClass()->where('name', $removed_tag_names)->get('tags', null, 'id, name');
      $removed_tags = array_reduce($removed_tags, function ($acc, $el) {
        $acc[$el['id']] = $el['name'];

        return $acc;
      }, []);
      $removed_tag_ids = array_keys($removed_tags);
      if (!empty($removed_tag_ids))
        DB::$instance->where('tag_id', $removed_tag_ids)->where('appearance_id', $this->id)->delete(Tagged::$table_name);
      foreach ($removed_tags as $tag_id => $tag_name){
        TagChange::record(false, $tag_id, $tag_name, $this->id);
        Tags::updateUses($tag_id);
      }
    }

    foreach ($added_tag_names as $name){
      $_REQUEST['tag_name'] = CoreUtils::trim($name);
      if (empty($_REQUEST['tag_name']))
        continue;

      $tag_name = CGUtils::validateTagName('tag_name');
      $tag_type = null;

      $tag = Tags::getActual($tag_name, 'name');
      if (empty($tag))
        $tag = Tag::create([
          'name' => $tag_name,
          'type' => $tag_type,
        ]);

      $this->addTag($tag);
      if (!empty(CGUtils::GROUP_TAG_IDS_ASSOC[$guide][$tag->id]))
        Appearances::getSortReorder($guide);
    }
  }

  /**
   * @param Tag  $tag
   * @param bool $update_uses
   *
   * @return self
   */
  public function addTag(Tag $tag, bool $update_uses = true):self {
    try {
      $created = Tagged::make($tag->id, $this->id)->save();
    }
    catch (DatabaseException $e){
      // Relation already exists, moving on
      if (CoreUtils::contains($e->getMessage(), 'duplicate key value violates unique constraint "tagged_pkey"')){
        return $this;
      }

      $created = false;
    }
    if (!$created){
      CoreUtils::logError(__METHOD__.": Failed to add tag {$tag->name} (#{$tag->id}) to appearance {$this->label} (#{$this->id}), skipping");

      return $this;
    }
    TagChange::record(true, $tag->id, $tag->name, $this->id);
    if ($update_uses)
      $tag->updateUses();

    return $this;
  }

  public function getSpriteFilePath() {
    return CGUtils::getSpriteFilePath($this->id, $this->owner_id !== null);
  }

  public function deleteSprite(?string $path = null, bool $silent = false) {
    if (!CoreUtils::deleteFile($path ?? $this->getSpriteFilePath())){
      if ($silent)
        return;
      Response::fail('File could not be deleted');
    }
    $this->sprite_hash = null;
    $this->save();
    $this->clearRenderedImages();
  }

  public static function checkCreatePermission(User $user, bool $personal) {
    if (!$personal){
      if (!$user->perm('staff'))
        Response::fail("You don't have permission to add appearances to the official Color Guide");
    }
    else {
      $availPoints = $user->getPCGAvailablePoints(false);
      if ($availPoints < 10){
        $remain = Users::calculatePersonalCGNextSlot($user->getPCGAppearanceCount());
        Response::fail("You don't have enough slots to create another appearance. Delete other ones or finish $remain more ".CoreUtils::makePlural('request', $remain).'. Visit <a href="/u">your profile</a> and click the <strong class="color-darkblue"><span class="typcn typcn-info-large"></span> What?</strong> button next to the Personal Color Guide heading for more information.');
      }
      if (!UserPrefs::get('a_pcgmake', $user))
        Response::fail(Appearances::PCG_APPEARANCE_MAKE_DISABLED);
    }
  }

  public function enforceManagePermission() {
    if (!Auth::$signed_in || (Auth::$user->id !== $this->owner_id && !Auth::$user->perm('staff')))
      CoreUtils::noPerm();
  }

  public function getShareURL(bool $can_see_token = false):string {
    return rtrim(ABSPATH, '/').$this->toURL(true).($can_see_token && $this->private ? "?token={$this->token}" : '');
  }

  public function hasTags():bool {
    return DB::$instance->where('appearance_id', $this->id)->has('tagged');
  }

  /**
   * For Twig
   *
   * @return bool
   */
  public function getPinned():bool {
    return $this->pinned;
  }

  /**
   * For Twig
   *
   * @return Cutiemark[]
   */
  public function getCutiemarks():array {
    return $this->cutiemarks;
  }

  public function getPreviewImage(int $size = self::SPRITE_SIZES['SOURCE']) {
    return $this->getSpriteURL($size, $this->getPreviewURL());
  }

  public function getBabelLabel():?string {
    if ($this->owner_id !== null || !CoreUtils::$useNutshellNames)
      return $this->label;

    if (!isset(NUTSHELL_NAMES[$this->id]))
      return strtolower($this->label);

    return CoreUtils::array_random(NUTSHELL_NAMES[$this->id]);
  }
}
