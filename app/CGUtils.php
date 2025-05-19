<?php

namespace App;

use App\Models\Appearance;
use App\Models\Color;
use App\Models\ColorGroup;
use App\Models\Cutiemark;
use App\Models\MajorChange;
use App\Models\PCGSlotHistory;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Elasticsearch\Common\Exceptions\ServerErrorResponseException;
use Exception;
use Generator;
use ONGR\ElasticsearchDSL;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use RuntimeException;
use SeinopSys\RGBAColor;
use function array_map;
use function array_slice;
use function count;
use function in_array;
use function is_array;

class CGUtils {
  public const GUIDE_FIM = 'pony';
  public const GUIDE_EQG = 'eqg';

  public const GUIDE_MAP = [
    self::GUIDE_FIM => 'Friendship is Magic',
    self::GUIDE_EQG => 'Equestria Girls',
  ];

  /** Used in Twig */
  public const ADD_NEW_NOUN = [
    self::GUIDE_FIM => 'Pony',
    self::GUIDE_EQG => 'Character',
  ];

  public const FULL_LIST_NOUN = [
    self::GUIDE_FIM => 'FiM Pony',
    self::GUIDE_EQG => 'EQG Character',
  ];

  public const GROUP_TAG_IDS_ASSOC = [
    self::GUIDE_FIM => [
      664 => 'Main Cast',
      45 => 'Cutie Mark Crusaders',
      59 => 'Royalty',
      666 => 'Student Six',
      9 => 'Antagonists',
      44 => 'Foals',
      78 => 'Original Characters',
      1 => 'Unicorns',
      3 => 'Pegasi',
      2 => 'Earth Ponies',
      10 => 'Pets',
      437 => 'Non-pony Characters',
      385 => 'Creatures',
      96 => 'Outfits & Clothing',
      // add other tags here
      64 => 'Objects',
      -1 => 'Other',
    ],
    self::GUIDE_EQG => [
      76 => 'Humans',
      -1 => 'Other',
    ],
  ];

  /**
   * Response creator for typeahead.js
   *
   * @param string|array $str
   */
  public static function autocompleteRespond($str):void {
    header('Content-Type: application/json');
    if (is_array($str)) {
      $result = JSON::encode($str);
      if ($result === false)
        throw new RuntimeException(__METHOD__.': failed to JSON encode parameter: '.var_export($str, true));
      /** @var string $result */
      $str = $result;
    }
    die($str);
  }

  /**
   * Returns HTML for the full list
   *
   * @param Appearance[] $appearances
   * @param string       $order_by
   * @param string       $guide
   * @param bool         $wrap
   *
   * @return string
   */
  public static function getFullListHTML(array $appearances, $order_by, ?string $guide, $wrap = WRAP):string {
    $HTML = '';
    $char_tags = DB::$instance->query(
        "SELECT t.name, tg.appearance_id FROM tags t
				LEFT JOIN tagged tg ON tg.tag_id = t.id OR tg.tag_id = t.synonym_of
				WHERE t.type = 'char'");
		$indexed_char_tags = array_reduce($char_tags, function($acc, $tag) {
		  $acc[$tag['appearance_id']][] = $tag['name'];
      return $acc;
		}, []);
    if (!empty($appearances)){
      $previews = !empty(UserPrefs::get('cg_fulllstprev'));
      switch ($order_by){
        case 'label':
          $PrevFirstLetter = '';
          foreach ($appearances as $p){
            $FirstLetter = strtoupper($p->label[0]);
            if (!preg_match('/^[A-Z]$/', $FirstLetter))
              $FirstLetter = '#';
            if (!is_numeric($FirstLetter) ? ($FirstLetter !== $PrevFirstLetter) : !is_numeric($PrevFirstLetter)){
              if ($PrevFirstLetter !== ''){
                $HTML .= '</ul></section>';
              }
              $PrevFirstLetter = $FirstLetter;
              $HTML .= "<section><h2>$PrevFirstLetter</h2><ul>";
            }
            self::_processFullListLink($p, $HTML, $indexed_char_tags[$p->id] ?? [], $previews);
          }
        break;
        case 'relevance':
          $sorted = Appearances::sort($appearances, $guide);
          foreach (self::GROUP_TAG_IDS_ASSOC[$guide] as $Category => $CategoryName){
            if (empty($sorted[$Category]))
              continue;

            $HTML .= "<section><h2>$CategoryName<button class='sort-alpha blue typcn typcn-sort-alphabetically hidden' title='Sort this section alphabetically'></button></h2><ul>";
            /** @var $sorted Appearance[][] */
            foreach ($sorted[$Category] as $p)
              self::_processFullListLink($p, $HTML, $indexed_char_tags[$p->id] ?? [], $previews);
            $HTML .= '</ul></section>';
          }
        break;
        case 'added':
          $HTML .= "<section><ul class='justify'>";
          /** @var $sorted Appearance[][] */
          foreach ($appearances as $p)
            self::_processFullListLink($p, $HTML, $indexed_char_tags[$p->id] ?? [], $previews);
          $HTML .= '</ul></section>';
        break;
        default:
          Response::fail("Unknown full list sorting order: $order_by");
      }
    }

    return $wrap ? "<div id='full-list'>$HTML</div>" : $HTML;
  }

  /**
   * @param Appearance $appearance
   * @param string     $HTML
   * @param string[]   $char_tags Contains the names of all characters tags on the appearance
   * @param bool       $previews
   */
  private static function _processFullListLink(Appearance $appearance, &$HTML, $char_tags, bool $previews):void {
    $sprite = '';
    $url = "/cg/v/{$appearance->id}-".$appearance->getURLSafeLabel();
    if (Permission::sufficient('staff')){
      $SpriteURL = $appearance->getSpriteURL();
      if (!empty($SpriteURL)){
        if (!$previews)
          $sprite = "<span class='typcn typcn-image' title='Has a sprite'></span>&nbsp;";
        $class = 'color-green';
      }
      if (!empty($appearance->private))
        $class = 'color-orange';
      if (!empty($class))
        $url .= "' class='$class";
    }

    if ($previews){
      $has_sprite = $appearance->hasSprite();
      $preview_url = $appearance->getPreviewURL();
      $static_sprite_url = $appearance->getStaticSpriteURL();
      $attributes = $has_sprite ? "data-src='$static_sprite_url' data-fallback='$preview_url'" : "data-src='$preview_url'";
      $class = !$has_sprite ? ' border' : '';
      $preview = "<span class='appearance-preview-promise$class' $attributes></span>";
      if (!empty($char_tags)){
        $aka = [];
        if (CoreUtils::$useNutshellNames && isset(NUTSHELL_NAMES[$appearance->id]))
          $aka[] = strtolower($appearance->label);
        foreach ($char_tags as $t){
          if (CoreUtils::contains($appearance->label, $t, false))
            continue;

          $aka[] = $t;
        }
        if (!empty($aka))
          $aka = '<span class="aka"><abbr title="Also known as">AKA</abbr> '.implode(', ', $aka).'</span>';
      }
    }
    else $preview = '';
    if (empty($aka))
      $aka = '';

    $HTML .= "<li><a href='$url'>$preview<span class='name'>$sprite<span class='event-label'>{$appearance->getBabelLabel()}</span></span>$aka</a></li>";
  }

  /**
   * Function to process uploaded images
   * Checks the $_FILES array for an item named $key,
   *  checks if file is an image, and it's mime type
   *  can be found in $allowedMimeTypes, and finally
   *  checks if the size is at least $minwidth by $minheight,
   *  then moves it to the requested $path.
   *
   * @param string     $key
   * @param string     $path
   * @param array|null $allowedMimeTypes
   * @param int[]      $min
   * @param int[]      $max
   *
   * @return void
   */
  public static function processUploadedImage($key, $path, $allowedMimeTypes, $min = null, $max = null):void {
    $minwidth = $min[0] ?? 1;
    $minheight = $min[1] ?? $minwidth;
    $maxwidth = $max[0] ?? 1000;
    $maxheight = $max[1] ?? $maxwidth;
    $min = [$minwidth, $minheight];
    $max = [$maxwidth, $maxheight];

    if (!isset($_FILES[$key])){
      self::grabImage($path, $allowedMimeTypes, $min, $max);

      return;
    }
    $file = $_FILES[$key];
    $tmp = $file['tmp_name'];
    if (empty($tmp))
      Response::fail('File upload failed; Reason unknown');

    [$width, $height] = Image::checkType($tmp, $allowedMimeTypes);
    Image::checkSize($tmp, $width, $height, $min, $max);
    CoreUtils::createFoldersFor($path);

    if (!move_uploaded_file($tmp, $path)){
      CoreUtils::deleteFile($tmp);
      Response::fail('File upload failed; Writing image file was unsuccessful');
    }
  }

  /**
   * Gets the uploaded image for process_uploaded_image
   *
   * @param string     $path
   * @param array|null $allowedMimeTypes
   * @param array      $min
   * @param array      $max
   */
  public static function grabImage(string $path, $allowedMimeTypes, array $min, array $max):void {
    try {
      $Image = new ImageProvider(Posts::validateImageURL());
    }
    catch (Exception $e){
      Response::fail($e->getMessage());
    }

    if ($Image->fullsize === false)
      Response::fail('Image could not be retrieved from external provider');

    $remoteFile = @File::get($Image->fullsize);
    if (empty($remoteFile))
      Response::fail('Remote file could not be found');
    if (File::put($path, $remoteFile) === false)
      Response::fail('Writing local image file was unsuccessful');

    [$width, $height] = Image::checkType($path, $allowedMimeTypes);
    Image::checkSize($path, $width, $height, $min, $max);
  }

  public const CHANGES_SECTION = <<<HTML
		<section>
			<h2><span class='typcn typcn-warning'></span>List of major changes</h2>
			@
		</section>
		HTML;

  /**
   * Renders HTML of the list of changes
   *
   * @param MajorChange[] $changes
   * @param bool          $wrap
   *
   * @return string
   * @throws Exception
   */
  public static function getMajorChangesHTML(?array $changes, bool $wrap = WRAP):string {
    return Twig::$env->render('colorguide/_major_changes.html.twig', [
      'changes' => $changes,
      'wrap' => $wrap,
    ]);
  }

  public static function processPCGSlotHistoryData(string $type, ?string $data):?string {
    if ($data === null){
      if ($type === 'free_trial')
        return "This one's on the house";

      return '&mdash;';
    }

    $data = JSON::decode($data);
    switch ($type){
      case 'post_approved':
      case 'post_unapproved':
        /** @var $post Post|null */
        $post = Post::find($data['id']);
        $label = "Post #{$data['id']}";
        if (!empty($post))
          return $post->toAnchor($label);

        return "$label <span class='color-red typcn typcn-trash' title='Deleted'></span>";
      case 'appearance_add':
      case 'appearance_del':
        /** @var $appearance Appearance|null */
        $appearance = Appearance::find($data['id']);
        $label = "{$data['label']} (#{$data['id']})";
        if (!empty($appearance))
          return $appearance->toAnchorWithPreview();

        return "$label <span class='color-red typcn typcn-trash' title='Deleted'></span>";
      case 'manual_give':
      case 'manual_take':
        if (Permission::sufficient('staff')) {
          $by = Users::resolveById($data['by']);
          $link = empty($by) ? 'an unknown user' : $by->toAnchor();
        }
        else $link = 'a staff member';

        return "By $link".
          (!empty($data['comment']) ? '<br><q>'.CoreUtils::escapeHTML($data['comment']).'</q>' : '');
      default:
        return '<pre>'.htmlspecialchars(JSON::encode($data, JSON_PRETTY_PRINT)).'</pre>';
    }
  }

  /**
   * Renders HTML of a user's slot history
   *
   * @param PCGSlotHistory[] $Entries
   * @param bool             $wrap
   *
   * @return string
   * @throws Exception
   */
  public static function getPCGSlotHistoryHTML(?array $Entries, bool $wrap = WRAP):string {
    $HTML = '';
    if (is_array($Entries))
      foreach ($Entries as $entry){
        $type = PCGSlotHistory::CHANGE_DESC[$entry->change_type];
        $data = self::processPCGSlotHistoryData($entry->change_type, $entry->change_data);
        $when = Time::tag($entry->created_at);
        $dir = $entry->change_amount > 0 ? 'pos' : 'neg';
        $amount = ($entry->change_amount > 0 ? "\u{2B}$entry->change_amount" : "\u{2212}".(-$entry->change_amount));

        $HTML .= <<<HTML
					<tr class="change-$dir">
						<td>$type</td>
						<td>$data</td>
						<td>$amount</td>
						<td><span class="typcn typcn-time"></span> $when</td>
					</tr>
					HTML;
      }
    if (!$wrap)
      return $HTML;

    return <<<HTML
			<div class="responsive-table">
			<table id='history-entries'>
				<thead>
					<th>Reason</th>
					<th>Details</th>
					<th>Amount</th>
					<th>When</th>
				</thead>
				<tbody>$HTML</tbody>
			</table>
			</div>
			HTML;
  }

  /**
   * Render appearance PNG image
   *
   * @param string     $CGPath
   * @param Appearance $Appearance
   *
   * @throws Exception
   * @noinspection AdditionOperationOnArraysInspection
   */
  public static function renderAppearancePNG($CGPath, Appearance $Appearance):void {
    $output_path = $Appearance->getPaletteFilePath();
    $file_relative_path = "$CGPath/v/{$Appearance->id}p.png";
    CoreUtils::fixPath($file_relative_path);
    if (file_exists($output_path))
      Image::outputPNG(null, $output_path, $file_relative_path);

    $output_height = 0;
    $sprite_width = 0;
    $sprite_height = 0;
    $sprite_right_margin = 10;
    $color_circle_size = 17;
    $color_circle_right_margin = 5;
    $color_name_font_size = 12;
    $regular_font_file = APPATH.'font/Celestia Redux Alternate.ttf';
    $pixelated_font_file = APPATH.'font/PixelOperator.ttf';
    if (!file_exists($regular_font_file))
      throw new RuntimeException('Font file missing');
    if (!file_exists($pixelated_font_file))
      throw new RuntimeException('Font file missing');
    $name = $Appearance->label;
    $name_vertical_margin = 5;
    $name_font_size = 22;
    $text_margin = 10;
    $output_color_count = 0;
    $split_threshold = 12;
    $column_right_margin = 20;

    // Detect if sprite exists and adjust image size & define starting positions
    $sprite_path = $Appearance->getSpriteFilePath()."{$Appearance->id}.png";
    $sprite_exists = file_exists($sprite_path);
    if ($sprite_exists){
      /** @var $sprite_size int[]|false */
      $sprite_size = getimagesize($sprite_path);
      if ($sprite_size === false)
        throw new RuntimeException("The sprite image located at $sprite_path could not be loaded by getimagesize");

      $sprite_image = imagecreatefrompng($sprite_path);
      /** @var $SpriteSize array */
      $sprite_height = $sprite_size[HEIGHT];
      $sprite_width = $sprite_size[WIDTH];
      $sprite_outer_width = $sprite_width + $sprite_right_margin;

      $output_height = $sprite_height;
    }
    else $sprite_outer_width = 0;
    $origin = [
      'x' => $sprite_exists ? $sprite_outer_width : $text_margin,
      'y' => 0,
    ];

    // Get color groups & calculate the space they take up
    $color_groups = $Appearance->color_groups;
    $cg_font_size = (int)round($name_font_size * 0.75);
    $cg_vertical_margin = $name_vertical_margin;
    /** @noinspection SpellCheckingInspection */
    $test_string = 'ABCDEFGIJKLMOPQRSTUVWQYZabcdefghijklmnopqrstuvwxyz/()}{@&#><';
    $group_label_box = Image::saneGetTTFBox($cg_font_size, $regular_font_file, $test_string);

    // Get export time & size
    $export_ts = [
      'Generated at: '.Time::format(time(), Time::FORMAT_FULL),
      'Source: '.rtrim(ABSPATH, '/').$Appearance->toURL(),
    ];
    $export_font_size = (int)round($cg_font_size * 0.7);
    $export_box = Image::saneGetTTFBox($export_font_size, $pixelated_font_file, $export_ts);

    // Get re-post warning
    $repost_warning = [
      'Please do not re-post this image on other sites to avoid spreading a',
      'particular version around that could become out of date in the future.',
    ];
    $repost_font_size = (int)round($cg_font_size * 0.6);
    $repost_box = Image::saneGetTTFBox($repost_font_size, $pixelated_font_file, $repost_warning);

    // Check how long & tall appearance name is, and set image width
    $name_box = Image::saneGetTTFBox($name_font_size, $regular_font_file, $name);
    $output_width = $origin['x'] + max($name_box['width'], $export_box['width'], $repost_box['width']) + $text_margin;

    // Set image height
    $output_height = max($origin['y'] + (($name_vertical_margin * 4) + $name_box['height'] + $export_box['height'] + $repost_box['height']), $output_height);

    // Create base image
    $base_image = Image::createTransparent($output_width, $output_height);
    $c_black = imagecolorallocate($base_image, 0, 0, 0);
    $c_dark_red = imagecolorallocate($base_image, 127, 0, 0);

    // If sprite exists, output it on base image
    if ($sprite_exists)
      Image::copyExact($base_image, $sprite_image, 0, 0, $sprite_width, $sprite_height);

    // Output appearance name
    $origin['y'] += $name_vertical_margin * 2;
    Image::writeOn($base_image, $name, $origin['x'], $name_font_size, $c_black, $origin, $regular_font_file);
    $origin['y'] += $name_vertical_margin;

    // Output generation time
    Image::writeOn($base_image, $export_ts, $origin['x'], $export_font_size, $c_black, $origin, $pixelated_font_file);
    $origin['y'] += $name_vertical_margin;

    // Output re-post warning
    Image::writeOn($base_image, $repost_warning, $origin['x'], $repost_font_size, $c_dark_red, $origin, $pixelated_font_file);
    $origin['y'] += $name_vertical_margin * 2;

    if (!empty($color_groups)){
      $cg_start_y = $origin['y'];
      $cg_largest_x = 0;
      $all_colors = self::getColorsForEach($color_groups);
      foreach ($color_groups as $cg){
        $cg_label_box = Image::saneGetTTFBox($cg_font_size, $regular_font_file, $cg->label);
        Image::calcRedraw($output_width, $output_height, $cg_label_box['width'] + $text_margin, $group_label_box['height'] + $name_vertical_margin + $cg_vertical_margin, $base_image, $origin);
        Image::writeOn($base_image, $cg->label, $origin['x'], $cg_font_size, $c_black, $origin, $regular_font_file, $group_label_box);
        $origin['y'] += $group_label_box['height'] + $cg_vertical_margin;

        if ($cg_label_box['width'] > $cg_largest_x){
          $cg_largest_x = $cg_label_box['width'];
        }

        if (!empty($all_colors[$cg->id])){
          $y_offset = -1;
          foreach ($all_colors[$cg->id] as $c){
            $color_name_left_offset = $color_circle_size + $color_circle_right_margin;
            $color_name_box = Image::saneGetTTFBox($color_name_font_size, $regular_font_file, $c->label);

            $width_increase = $color_name_left_offset + $color_name_box['width'] + $text_margin;
            $height_increase = max($color_circle_size, $color_name_box['height']) + $cg_vertical_margin;
            Image::calcRedraw($output_width, $output_height, $width_increase, $height_increase, $base_image, $origin);

            Image::drawCircle($base_image, $origin['x'], $origin['y'], [$color_circle_size, $color_circle_size], $c->hex, $c_black);

            Image::writeOn($base_image, $c->label, $origin['x'] + $color_name_left_offset, $color_name_font_size, $c_black, $origin, $regular_font_file, $color_name_box, $y_offset);
            $origin['y'] += $height_increase;

            $output_color_count++;

            $total_width = $color_name_left_offset + $color_name_box['width'];
            if ($total_width > $cg_largest_x){
              $cg_largest_x = $total_width;
            }
          }
        }

        if ($output_color_count > $split_threshold){
          Image::calcRedraw($output_width, $output_height, 0, $name_vertical_margin, $base_image, $origin);
          $origin['y'] = $cg_start_y;
          $origin['x'] += $cg_largest_x + $column_right_margin;
          $output_color_count = 0;
          $cg_largest_x = 0;
        }
        else $origin['y'] += $name_vertical_margin;
      }
    }

    $final_base = Image::createWhiteBG($output_width, $output_height);
    Image::drawSquare($final_base, 0, 0, [$output_width, $output_height], null, $c_black);
    Image::copyExact($final_base, $base_image, 0, 0, $output_width, $output_height);

    if (!CoreUtils::createFoldersFor($output_path))
      Response::fail('Failed to create render directory');
    Image::outputPNG($final_base, $output_path, $file_relative_path);
  }

  public const CMDIR_SVG_PATH = FSPATH.'cg_render/appearance/#/cmdir-@.svg';

  // Generate appearance facing image (CM background)
  public static function renderCMFacingSVG(Appearance $appearance):void {
    $facing = $_GET['facing'] ?? 'left';
    if (!in_array($facing, Cutiemarks::VALID_FACING_VALUES, true))
      Response::fail('Invalid facing value specified!');

    $output_path = str_replace(['#', '@'], [$appearance->id, $facing], self::CMDIR_SVG_PATH);
    $file_rel_path = $appearance->getFacingSVGURL($facing, false);
    if (file_exists($output_path))
      Image::outputSVG(null, $output_path, $file_rel_path);

    $color_mapping = $appearance->getColorMapping(Appearance::DEFAULT_COLOR_MAPPING);

    $img = File::get(APPATH.'img/cm_facing/'.($facing === CM_FACING_RIGHT ? 'right' : 'left').'.svg');
    foreach (Appearance::DEFAULT_COLOR_MAPPING as $label => $defhex)
      $img = str_replace($label, $color_mapping[$label] ?? $defhex, $img);

    Image::outputSVG($img, $output_path, $file_rel_path);
  }

  public static function renderCMSVG(Cutiemark $CutieMark, bool $output = true):void {
    if (empty($CutieMark))
      CoreUtils::notFound();

    $output_path = $CutieMark->getRenderedFilePath();
    $file_rel_path = $CutieMark->getRenderedRelativeURL();
    if (file_exists($output_path))
      Image::outputSVG(null, $output_path, $file_rel_path);

    $tokenized = $CutieMark->getTokenizedFile();
    if ($tokenized === null)
      CoreUtils::notFound();
    $img = self::untokenizeSvg($tokenized, $CutieMark->appearance_id);
    if (!$output){
      File::put($output_path, $img);

      return;
    }
    Image::outputSVG($img, $output_path, $file_rel_path);
  }

  public static function int2Hex(int $int):string {
    return '#'.strtoupper(CoreUtils::pad(dechex($int), 6));
  }

  private static function _coordGenerator($w, $h):?Generator {
    for ($y = 0; $y < $h; $y++){
      for ($x = 0; $x < $w; $x++)
        yield [$x, $y];
    }
  }

  public static function getSpriteImageMap($AppearanceID, $pcg) {
    $png_path = self::getSpriteFilePath($AppearanceID, $pcg);
    $map_file = CachedFile::init(FSPATH."cg_render/appearance/$AppearanceID/linedata.json.gz", static function ($path) use ($png_path) {
      return !file_exists($path) || filemtime($path) < filemtime($png_path);
    });
    if (!$map_file->expired())
      $map = $map_file->read();
    else {
      if (!file_exists($png_path))
        Response::fail("There's no sprite image for appearance #$AppearanceID");

      $img_size = getimagesize($png_path);
      if ($img_size === false){
        throw new RuntimeException("getimagesize failed to read sprite $png_path");
      }
      [$png_width, $png_height] = $img_size;
      $png = imagecreatefrompng($png_path);
      if ($png === false) {
        throw new RuntimeException("Could not create image from path $png_path");
      }

      imagesavealpha($png, true);

      $all_colors = [];
      foreach (self::_coordGenerator($png_width, $png_height) as $pos){
        [$x, $y] = $pos;
        $rgb = imagecolorat($png, $x, $y);
        $colors = imagecolorsforindex($png, $rgb);
        $hex = strtoupper('#'.CoreUtils::pad(dechex($colors['red'])).CoreUtils::pad(dechex($colors['green'])).CoreUtils::pad(dechex($colors['blue'])));
        $opacity = $colors['alpha'] ?? 0;
        if ($opacity === 127)
          continue;
        $all_colors[$hex][$opacity][] = [$x, $y];
      }

      $current_line = null;
      $lines = [];
      $last_x = -2;
      $last_y = -2;
      $_colors_assoc = [];
      $color_no = 0;
      foreach ($all_colors as $hex => $opacities){
        if (!isset($_colors_assoc[$hex])){
          $_colors_assoc[$hex] = $color_no;
          $color_no++;
        }
        foreach ($opacities as $opacity => $coords){
          foreach ($coords as $pos){
            [$x, $y] = $pos;

            if ($x - 1 !== $last_x || $y !== $last_y){
              if ($current_line !== null)
                $lines[] = $current_line;
              $current_line = [
                'x' => $x,
                'y' => $y,
                'width' => 1,
                'colorid' => $_colors_assoc[$hex],
                'opacity' => $opacity,
              ];
            }
            else $current_line['width']++;

            $last_x = $x;
            $last_y = $y;
          }
        }
      }
      if ($current_line !== null)
        $lines[] = $current_line;

      $output = [
        'width' => $png_width,
        'height' => $png_height,
        'linedata' => [],
        'colors' => array_flip($_colors_assoc),
      ];
      foreach ($lines as $line)
        $output['linedata'][] = $line;

      $map = $output;
      $map_file->update($output);
    }

    return $map;
  }

  /**
   * @param string      $CGPath
   * @param Appearance  $appearance
   * @param string|null $wanted_size
   */
  public static function renderSpritePNG($appearance, $wanted_size = null):void {
    $appearance_id = $appearance->id;
    $pcg = $appearance->owner_id !== null;
    $size = $wanted_size !== null ? (int)$wanted_size : null;
    if (!in_array($size, Appearance::SPRITE_SIZES, true))
      $size = 600;
    $outsize = $size === Appearance::SPRITE_SIZES['REGULAR'] ? '' : "-$size";

    $output_path = FSPATH."cg_render/appearance/{$appearance_id}/sprite$outsize.png";
    if (file_exists($output_path))
      Image::outputPNGAPI(null, $output_path);

    $map = self::getSpriteImageMap($appearance_id, $pcg);

    $size_factor = (int)round($size / 300);
    $png = Image::createTransparent($map['width'] * $size_factor, $map['height'] * $size_factor);
    foreach ($map['linedata'] as $line){
      $map_color = $map['colors'][$line['colorid']];
      $rgb = RGBAColor::parse($map_color);
      if ($rgb === null)
        throw new RuntimeException(__METHOD__.': Failed to parse color value '.var_export($map_color, true));
      $color = imagecolorallocatealpha($png, $rgb->red, $rgb->green, $rgb->blue, $line['opacity']);
      Image::drawSquare($png, $line['x'] * $size_factor, $line['y'] * $size_factor, [$line['width'] * $size_factor, $size_factor], $color, null);
    }

    Image::outputPNGAPI($png, $output_path);
  }

  public static function renderSpriteSVG($CGPath, Appearance $appearance):void {
    $appearance_id = $appearance->id;
    $pcg = $appearance->owner_id !== null;
    $map = self::getSpriteImageMap($appearance_id, $pcg);
    if (empty($map))
      CoreUtils::notFound();

    $output_path = FSPATH."cg_render/appearance/{$appearance_id}/sprite.svg";
    $file_rel_path = "$CGPath/v/{$appearance_id}s.svg";
    if (file_exists($output_path))
      Image::outputSVG(null, $output_path, $file_rel_path);

    $img_width = $map['width'];
    $img_height = $map['height'];
    $strokes = [];
    foreach ($map['linedata'] as $line){
      $hex = $map['colors'][$line['colorid']];
      if ($line['opacity'] !== 0){
        $opacity = (float)number_format((127 - $line['opacity']) / 127, 2, '.', '');
        $hex .= "' opacity='{$opacity}";
      }
      $strokes[$hex][] = "M{$line['x']} {$line['y']} l{$line['width']} 0Z";
    }
    $svg = <<<XML
			<svg version='1.1' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 $img_width $img_height' enable-background='new 0 0 $img_width $img_height' xml:space='preserve'>
			XML;
    foreach ($strokes as $hex => $defs){
      $d = '';
      foreach ($defs as $def)
        $d .= "$def ";
      $d = rtrim($d);
      $svg .= /** @lang XML */
        "<path stroke='$hex' d='$d'/>";
    }
    $svg .= '</svg>';

    Image::outputSVG($svg, $output_path, $file_rel_path);
  }

  public const PREVIEW_SVG_PATH = FSPATH.'appearance_previews/#.svg';

  public static function renderPreviewSVG(Appearance $Appearance, bool $output = true):void {
    $preview_colors = self::colorsToHexes($Appearance->getPreviewColors());
    $output_path = str_replace('#', self::hexesToFilename($preview_colors), self::PREVIEW_SVG_PATH);
    $file_rel_path = "/img/appearance_previews/{$Appearance->id}.svg";
    if (file_exists($output_path)) {
      if (!$output)
        return;
      Image::outputSVG(null, $output_path, $file_rel_path);
    }

    $svg = '';
    $color_count = count($preview_colors);
    switch ($color_count){
      case 0:
        $svg .= '<rect fill="#FFFFFF" width="2" height="2"/><rect fill="#EFEFEF" width="1" height="1"/><rect fill="#EFEFEF" width="1" height="1" x="1" y="1"/>';
      break;
      case 1:
        $svg .= /** @lang XML */
          "<rect x='0' y='0' width='2' height='2' fill='{$preview_colors[0]}'/>";
      break;
      case 3:
        $svg .= <<<SVG
					<rect x='0' y='0' width='2' height='2' fill='{$preview_colors[0]}'/>
					<rect x='0' y='1' width='1' height='1' fill='{$preview_colors[1]}'/>
					<rect x='1' y='1' width='1' height='1' fill='{$preview_colors[2]}'/>
					SVG;
      break;
      case 2:
      case 4:
        $x = 0;
        $y = 0;
        foreach ($preview_colors as $c){
          $w = $x % 2 === 0 ? 2 : 1;
          $h = $y % 2 === 0 ? 2 : 1;
          $svg .= "<rect x='$x' y='$y' width='$w' height='$h' fill='{$c}'/>";
          $x++;
          if ($x > 1){
            $x = 0;
            $y = 1;
          }
        }
      break;
    }

    // Only apply blur if we have colors
    if ($color_count > 0)
      $svg = "<defs><filter id='b' x='0' y='0'><feGaussianBlur in='SourceGraphic' stdDeviation='0.4' /></filter></defs><g filter='url(#b)'>$svg</g>";

    $svg = /** @lang XML */
      "<svg version='1.1' xmlns='http://www.w3.org/2000/svg' viewBox='.5 .5 1 1' enable-background='new 0 0 2 2' xml:space='preserve' preserveAspectRatio='xMidYMid slice'>$svg</svg>";

    Image::outputSVG($svg, $output_path, $file_rel_path, $output);
  }

  /**
   * @param Appearance $Appearance
   */
  public static function getSwatchesAI(Appearance $Appearance):void {
    $label = $Appearance->label;
    $json = [
      'Exported at' => gmdate('Y-m-d H:i:s \G\M\T'),
      'Version' => '1.4',
    ];
    $json[$label] = [];

    $color_groups = $Appearance->color_groups;
    $colors = self::getColorsForEach($color_groups, true);
    foreach ($color_groups as $cg){
      if (!isset($colors[$cg->id])) continue;
      $json[$label][$cg->label] = [];
      foreach ($colors[$cg->id] as $c)
        $json[$label][$cg->label][$c->label] = $c->hex;
    }

    CoreUtils::downloadAsFile(JSON::encode($json), "$label.json");
  }

  /**
   * @param string   $name
   * @param array    $colors [ [r,g,b,label], ... ]
   * @param int|null $ts     Timestamp to be included in the file
   *
   * @return string
   */
  public static function generateGimpPalette(string $name, array $colors, ?int $ts = null):string {
    if ($ts === null)
      $ts = time();
    $export_ts = gmdate('Y-m-d H:i:s T', $ts);

    $file = <<<GPL
			GIMP Palette
			Name: $name
			Columns: 6
			#
			# Exported at: $export_ts
			#

			GPL;

    $file .= implode("\n", array_map(static function ($arr) {
      $arr[0] = CoreUtils::pad($arr[0], 3, ' ');
      $arr[1] = CoreUtils::pad($arr[1], 3, ' ');
      $arr[2] = CoreUtils::pad($arr[2], 3, ' ');
      if (isset($arr[3]))
        $arr[3] = htmlspecialchars($arr[3]);

      return implode(' ', $arr);
    }, $colors));

    return "$file\n";
  }

  /**
   * @param Appearance $appearance
   */
  public static function getSwatchesInkscape(Appearance $appearance):void {
    $label = $appearance->label;

    $color_groups = $appearance->color_groups;
    $colors = self::getColorsForEach($color_groups, true);
    $list = [];
    foreach ($color_groups as $cg){
      foreach ($colors[$cg->id] as $c){
        if (empty($c->hex))
          continue;
        $rgb = RGBAColor::parse($c->hex);
        if ($rgb === null)
          throw new RuntimeException(__METHOD__.': Failed to parse color value '.var_export($c->hex, true));
        $list[] = [
          $rgb->red,
          $rgb->green,
          $rgb->blue,
          "{$cg->label} | {$c->label}",
        ];
      }
    }

    CoreUtils::downloadAsFile(self::generateGimpPalette($label, $list), "$label.gpl");
  }

  /**
   * Detect all colors inside the SVG file & replace with a mapping to guide colors
   *
   * @param string $svg Image data
   * @param int    $appearance_id
   *
   * @return string Tokenized SVG file
   */
  public static function tokenizeSvg(string $svg, int $appearance_id):string {
    /** @var $CMColorGroup ColorGroup */
    $CMColorGroup = DB::$instance->where('label', 'Cutie Mark')->where('appearance_id', $appearance_id)->getOne(ColorGroup::$table_name);
    if (empty($CMColorGroup))
      return $svg;

    RGBAColor::forEachColorIn($svg, static function (RGBAColor $color) use ($CMColorGroup) {
      /** @var $dbcolor Color[] */
      $dbcolor = DB::$instance->where('hex', $color->toHex())->where('group_id', $CMColorGroup->id)->get(Color::$table_name);

      if (empty($dbcolor))
        return sprintf('<!--#/%s-->', mb_substr($color->toHexa(), 1));

      $id = '@'.$dbcolor[0]->id;
      if ($color->isTransparent())
        $id .= ','.$color->alpha;

      return "<!--$id-->";
    });

    return $svg;
  }

  /**
   * Detect tokenized colors inside SVG file & replace with colors from guide
   *
   * @param string $svg Image data
   * @param int    $appearance_id
   * @param array  $warnings
   *
   * @return string Un-tokenized SVG file
   */
  public static function untokenizeSvg(string $svg, int $appearance_id, ?array &$warnings = null):string {
    /** @var $cm_color_group ColorGroup */
    $cm_color_group = DB::$instance->where('label', 'Cutie Mark')->where('appearance_id', $appearance_id)->getOne(ColorGroup::$table_name);
    if (empty($cm_color_group))
      return $svg;

    if ($warnings !== null) {
      $color_warnings = [];
    }

    $svg = preg_replace_callback('/<!--@(\d+)(?:,([\d.]+))?-->/', static function ($match) {
      /** @var $dbcolor Color */
      $dbcolor = Color::find($match[1]);

      if (empty($dbcolor))
        return $match;

      $color = RGBAColor::parse($dbcolor->hex);
      if ($color === null)
        throw new RuntimeException(__METHOD__.': Failed to parse color value '.var_export($dbcolor->hex, true));
      $color->alpha = (float)(!empty($match[2]) ? $match[2] : 1);

      return (string)$color;
    }, $svg);
    $svg = preg_replace_callback('~<!--#/([A-F\d]{8})-->~', static function ($match) use (&$warnings, &$color_warnings) {
      $hex_without_hash = $match[1];
      $color = RGBAColor::parse("#$hex_without_hash");

      if ($warnings !== null && !isset($color_warnings[$hex_without_hash])) {
        $color_warnings[$hex_without_hash] = "Unexpected color $color (not found in Cutie Mark color group)";
      }
      return (string) $color;
    }, $svg);

    if (!empty($color_warnings)) {
      $warnings = array_merge($warnings, array_values($color_warnings));
    }

    return $svg;
  }

  public static function validateTagName($key):string {
    $name = strtolower((new Input($key, static function ($value, $range) {
      if (Input::checkStringLength($value, $range, $code))
        return $code;
      if (CoreUtils::contains($value, ','))
        return 'comma';
      if ($value[0] === '-')
        return 'dash';
    }, [
      Input::IN_RANGE => [2, 64],
      Input::CUSTOM_ERROR_MESSAGES => [
        Input::ERROR_MISSING => 'Tag name cannot be empty',
        Input::ERROR_INVALID => 'Tag name (@value) cannot be empty',
        Input::ERROR_RANGE => 'Tag name must be between @min and @max characters',
        'dash' => 'Tag name (@value) cannot start with a dash',
        'comma' => 'Tag name (@value) cannot contain commas',
      ],
    ]))->out());
    CoreUtils::checkStringValidity($name, 'Tag name', INVERSE_TAG_NAME_PATTERN);

    return $name;
  }

  public static $CM_DIR = [
    CM_FACING_LEFT => 'Head-tail',
    CM_FACING_RIGHT => 'Tail-head',
  ];

  public const ELASTIC_BASE = [
    'index' => 'appearances',
  ];

  /**
   * Performs an ElasticSearch search operation
   *
   * @param array      $body
   * @param Pagination $Pagination
   *
   * @return array
   */
  public static function searchElastic(array $body, Pagination $Pagination):array {
    $params = array_merge(self::ELASTIC_BASE, $Pagination->toElastic(), [
      'body' => $body,
    ]);

    return CoreUtils::elasticClient()->search($params);
  }

  /**
   * Get the colors belonging to a set of color groups
   *
   * @param ColorGroup[] $Groups
   * @param bool         $skip_null Whether to include "empty" colors with null HEX value
   *
   * @return Color[][]
   */
  public static function getColorsForEach($Groups, $skip_null = false):?array {
    if (empty($Groups)){
      return null;
    }

    $GroupIDs = [];
    foreach ($Groups as $g){
      $GroupIDs[] = $g->id;
    }

    $colors = Color::find('all', [
      'conditions' => [
        'group_id IN (?)'.($skip_null ? ' AND hex IS NOT NULL' : ''),
        $GroupIDs,
      ],
      'order' => 'group_id asc, "order" asc',
    ]);
    if (empty($colors)){
      return null;
    }

    $sorted = [];
    foreach ($colors as $row){
      $sorted[$row->group_id][] = $row;
    }

    return $sorted;
  }

  /**
   * @param Color[] $colors
   *
   * @return string|null
   */
  public static function stringifyColors(?array $colors):?string {
    if (empty($colors))
      return null;

    $return = array_map(fn($c) => "{$c->hex} {$c->label}", $colors);

    return implode("\n", $return);
  }

  /**
   * @param ColorGroup[] $cgs
   *
   * @return string
   */
  public static function stringifyColorGroups($cgs):string {
    if (empty($cgs))
      return '';

    $return = [];
    foreach ($cgs as $c)
      $return[] = $c->label;

    return implode("\n", $return);
  }

  public static function roundHex(string $hex):string {
    $color = RGBAColor::parse($hex);
    if ($color === null)
      throw new RuntimeException(__METHOD__.': Failed to parse color value '.var_export($hex, true));
    foreach (RGBAColor::COMPONENTS as $key){
      $value = &$color->{$key};
      if ($value <= 3)
        $value = 0;
      else if ($value >= 252)
        $value = 255;
    }

    return $color->toHex();
  }

  public static function getExportData() {
    $json = [
      'Appearances' => [],
      'Tags' => [],
    ];

    /** @var $tags Tag[] */
    $tags = DB::$instance->orderBy('id')->get('tags');
    if (!empty($tags)) foreach ($tags as $t){
      $json['Tags'][$t->id] = $t->to_array();
    }

    $appearances = Appearances::get(null);
    if (!empty($appearances)) foreach ($appearances as $p){
      $append_appearance = [
        'id' => $p->id,
        'order' => $p->order,
        'label' => $p->label,
        'notes' => $p->notes_src === null ? '' : CoreUtils::trim($p->notes_src, true),
        'guide' => $p->guide,
        'added' => gmdate('Y-m-d\TH:i:s\Z', $p->created_at->getTimestamp()),
        'private' => $p->private,
      ];

      $cms = Cutiemarks::get($p);
      if (!empty($cms)){
        $append_cms = [];
        foreach ($cms as $cm){
          $arr = [
            'facing' => $cm->facing,
            'svg' => $cm->getRenderedRelativeURL(),
          ];
          if ($cm->favme !== null)
            $arr['source'] = "http://fav.me/{$cm->favme}";
          if ($cm->contributor_id !== null)
            $arr['contributor'] = $cm->contributor->toURL();
          $append_cms[$cm->id] = $arr;
        }
        $append_appearance['CutieMark'] = $append_cms;
      }

      $append_appearance['ColorGroups'] = [];
      if (empty($append_appearance['private'])){
        $color_groups = $p->color_groups;
        if (!empty($color_groups)){
          $all_colors = self::getColorsForEach($color_groups);
          foreach ($color_groups as $cg){
            $append_color_group = $cg->to_array([
              'except' => 'appearance_id',
            ]);

            $append_color_group['Colors'] = [];
            if (!empty($all_colors[$cg->id])){
              /** @var $colors Color[] */
              $colors = $all_colors[$cg->id];
              foreach ($colors as $c)
                /** @noinspection UnsupportedStringOffsetOperationsInspection */
                $append_color_group['Colors'][] = $c->to_array([
                  'except' => ['id', 'group_id'],
                ]);
            }

            $append_appearance['ColorGroups'][$cg->id] = $append_color_group;
          }
        }
      }
      else $append_appearance['ColorGroups']['_hidden'] = true;

      $append_appearance['TagIDs'] = [];
      $tag_ids = Tags::getFor($p->id, null, true);
      if (!empty($tag_ids)){
        foreach ($tag_ids as $t)
          $append_appearance['TagIDs'][] = $t->id;
      }

      $append_appearance['RelatedAppearances'] = [];
      $related_ids = $p->related_appearances;
      if (!empty($related_ids))
        foreach ($related_ids as $rel)
          $append_appearance['RelatedAppearances'][] = $rel->target_id;

      $json['Appearances'][$append_appearance['id']] = $append_appearance;
    }

    return JSON::encode($json);
  }

  const GUIDE_EXPORT_PATH = APPATH.'dist/mlpvc-colorguide.json';

  public static function saveExportData():void {
    File::put(self::GUIDE_EXPORT_PATH, self::getExportData());
  }

  public static function isElasticAvailable():bool {
    try {
      $elastic_avail = CoreUtils::elasticClient()->ping();
    }
    catch (NoNodesAvailableException|ServerErrorResponseException $e){
      return false;
    }

    return $elastic_avail;
  }

  /**
   * @param Pagination  $pagination
   * @param string      $guide
   * @param bool        $searching
   * @param string|null $title
   *
   * @return array
   * @throws BadRequest400Exception
   * @throws ServerErrorResponseException
   */
  public static function searchGuide(Pagination $pagination, string $guide, bool $searching = true, string &$title = null):array {
    $search = new ElasticsearchDSL\Search();
    $in_order = true;

    // Search query exists
    if ($searching){
      $search_query = preg_replace("~[^\w\s*?'-]~", '', CoreUtils::trim($_GET['q']));
      if ($title !== null)
        $title .= "$search_query - ";
      $multi_match = new ElasticsearchDSL\Query\FullText\MultiMatchQuery(
        ['label', 'tags'],
        $search_query,
        [
          'type' => 'cross_fields',
          'minimum_should_match' => '100%',
        ]
      );
      $search->addQuery($multi_match);
    }

    $sort = new ElasticsearchDSL\Sort\FieldSort('order', 'asc');
    $search->addSort($sort);

    $bool_query = new BoolQuery();
    $bool_query->add(new TermQuery('guide', $guide), BoolQuery::MUST);
    $search->addQuery($bool_query);

    $search->setSource(false);
    $search = $search->toArray();
    try {
      $search = self::searchElastic($search, $pagination);
    }
    catch (Missing404Exception $e){
      $search = [];
    }
    catch (ServerErrorResponseException | BadRequest400Exception $e){
      $message = $e->getMessage();
      if (
        !CoreUtils::contains($message, 'Result window is too large, from + size must be less than or equal to')
        && !CoreUtils::contains($message, 'Failed to parse int parameter [from] with value')
      ){
        throw $e;
      }

      $search = [];
      $pagination->calcMaxPages(0);
    }

    if (!empty($search)){
      $total_hits = $search['hits']['total'];
      if (is_array($total_hits) && isset($total_hits['value']))
        $total_hits = $total_hits['value'];
      $pagination->calcMaxPages($total_hits);
      if (!empty($search['hits']['hits'])){
        $ids = [];
        /** @noinspection ForeachSourceInspection */
        foreach ($search['hits']['hits'] as $i => $hit)
          $ids[$hit['_id']] = $i;

        DB::$instance->orderBy('order')->where('id', array_keys($ids));
        $appearances = Appearances::get($guide);
        if (!empty($appearances) && !$in_order)
          usort($appearances, static function (Appearance $a, Appearance $b) use ($ids) {
            return $ids[$a->id] <=> $ids[$b->id];
          });
      }
    }

    return [$appearances ?? [], $search_query ?? null];
  }

  static function getSpritePreviewPath(int $appearance_id):string {
    return FSPATH."cg_render/appearance/$appearance_id/sprite-preview.png";
  }

  /**
   * @param int  $appearance_id
   * @param bool $pcg
   *
   * @return string Binary data of the preview image
   */
  static function generateSpritePreview(int $appearance_id, bool $pcg):string {
    $output_path = self::getSpritePreviewPath($appearance_id);

    if (!file_exists($output_path)){
      $sprite_path = self::getSpriteFilePath($appearance_id, $pcg);
      if (!file_exists($sprite_path)){
        throw new RuntimeException("Trying to get preview for non-exiting sprite file $sprite_path");
      }

      $sprite = imagecreatefrompng($sprite_path);
      $sprite_size = array_slice(getimagesize($sprite_path), 0, 2);
      $preview_scale_factor = .2;
      [$preview_width, $preview_height] = array_map(function (int $size) use ($preview_scale_factor) {
        return (int)round($size * $preview_scale_factor);
      }, $sprite_size);

      $preview = Image::createTransparent($preview_width, $preview_height);
      imagecopyresampled($preview, $sprite, 0, 0, 0, 0, $preview_width, $preview_height, ...$sprite_size);

      CoreUtils::createFoldersFor($output_path);
      imagepng($preview, $output_path);
      File::chmod($output_path);
    }

    return file_get_contents($output_path);
  }

  static function getSpriteFilePath(int $appearance_id, bool $pcg):string {
    return ($pcg ? PRIVATE_SPRITE_PATH : PUBLIC_SPRITE_PATH)."$appearance_id.png";
  }

  /**
   * @param Color[] $hexes Array of Color objects
   *
   * @return string[] Array of hex values
   */
  static function colorsToHexes(array $hexes):array {
    return array_map(fn(Color $el) => $el->hex, $hexes);
  }

  /**
   * @param string[]|Color[] $hexes Array of hex color values e.g. ["#AAAAAA", "#BBBBBB", "#CCCCCC"]
   *
   * @return string Concatenated color values in the format "AAAAAA_BBBBBB_CCCCCC"
   */
  static function hexesToFilename(array $hexes):string {
    $output = [];
    /** @var string|Color $color */
    foreach ($hexes as $color){
      if ($color instanceof Color)
        $output[] = $color->hex;
      else $output[] = $color;
    }

    if (empty($output))
      return 'default';

    return str_replace('#', '', implode('_', $output));
  }

  /**
   * @param User|null $user
   *
   * @return string
   */
  public static function redirectToPreferredGuidePath(?User $user = null):string {
    $pref = UserPrefs::get('cg_defaultguide', $user);

    HTTP::tempRedirect('/cg'.($pref === null ? '' : "/$pref"));
  }
}
