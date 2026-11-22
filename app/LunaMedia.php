<?php

declare(strict_types=1);

namespace App;

use App\Models\Media;

/**
 * Every image on this site comes from Luna: sprites and cutie marks are rows of its `media` table (the files live on its CDN host, or behind its
 * API for private appearances), and the palette, preview, facing and sprite SVG images are rendered by its API. Nothing is stored or rendered here.
 */
class LunaMedia {
  public const APPEARANCE_MODEL = 'App\\Models\\Appearance';
  public const SPRITES = 'sprites';
  public const DOUBLE_SIZE = '2x';
  /** Luna's SpriteSize::Default and ::Double */
  public const SIZE_DEFAULT = 300;
  public const SIZE_DOUBLE = 600;

  /** @var array<int, Media>|null sprite media by appearance ID, loaded once per request */
  private static ?array $sprites = null;

  public static function apiUrl():string {
    return rtrim((string)(CoreUtils::env('LUNA_API_URL') ?: 'https://api.mlpvector.club'), '/');
  }

  public static function cdnUrl():string {
    return rtrim((string)(CoreUtils::env('LUNA_CDN_URL') ?: 'https://mlpvcdn.top'), '/');
  }

  /**
   * A URL on Luna's API. The share token of a private appearance is passed on when the current request has one.
   *
   * @param array<string, scalar> $query
   */
  public static function api(string $path, array $query = []):string {
    if (!empty($_GET['token']) && is_string($_GET['token']))
      $query['token'] = $_GET['token'];

    return self::apiUrl().$path.($query ? '?'.http_build_query($query) : '');
  }

  public static function paletteImage(int $appearance_id):string {
    return self::api("/appearances/$appearance_id/image", ['type' => 'palette', 'format' => 'png']);
  }

  public static function previewImage(int $appearance_id):string {
    return self::api("/appearances/$appearance_id/image", ['type' => 'preview', 'format' => 'svg']);
  }

  public static function spriteSvg(int $appearance_id):string {
    return self::api("/appearances/$appearance_id/image", ['type' => 'sprite', 'format' => 'svg']);
  }

  public static function facingImage(int $appearance_id, string $facing):string {
    return self::api("/appearances/$appearance_id/image", ['type' => 'facing', 'format' => 'svg', 'facing' => $facing === 'right' ? 'right' : 'left']);
  }

  /** @param string $format json (Adobe Illustrator swatches) or gpl (GIMP/Inkscape palette) */
  public static function swatches(int $appearance_id, string $format):string {
    return self::api("/appearances/$appearance_id/palette", ['format' => $format]);
  }

  public static function cutieMark(int $cutiemark_id, bool $download = false):string {
    return self::api("/cutie-marks/$cutiemark_id/".($download ? 'download' : 'image'));
  }

  /**
   * Path of a media file on the CDN, relative to its root. Luna's PathGenerator puts each media in a folder named after its ID (offset by 36, in
   * base 36) under the collection name, its ConversionFileNamer names conversions `<file name without extension>-<conversion>.<extension>`.
   */
  public static function mediaPath(int $media_id, string $collection, string $file_name, ?string $conversion = null):string {
    $folder = "$collection/".base_convert((string)(36 + $media_id), 10, 36);
    if ($conversion === null)
      return "$folder/$file_name";

    $info = pathinfo($file_name);

    return "$folder/conversions/{$info['filename']}-$conversion".(isset($info['extension']) ? ".{$info['extension']}" : '');
  }

  /** The sprite of an appearance, null if it has none */
  public static function spriteMedia(int $appearance_id):?Media {
    if (self::$sprites === null){
      self::$sprites = [];
      // Newest last, so a replaced sprite wins. There are only a couple of hundred rows: one query serves every appearance of the page
      $rows = Media::find('all', [
        'conditions' => ['model_type = ? AND collection_name = ?', self::APPEARANCE_MODEL, self::SPRITES],
        'order' => 'id asc',
      ]);
      foreach ($rows ?? [] as $row)
        self::$sprites[(int)$row->model_id] = $row;
    }

    return self::$sprites[$appearance_id] ?? null;
  }

  /** Forget what was loaded (for tests) */
  public static function reset():void {
    self::$sprites = null;
  }

  /**
   * Where a browser can get the sprite: straight from the CDN for public appearances, from Luna's API (which checks the share token) for private ones
   */
  public static function spriteUrl(int $appearance_id, ?int $size = null):?string {
    $media = self::spriteMedia($appearance_id);
    if ($media === null)
      return null;

    $double = $size === self::SIZE_DOUBLE;
    if ($media->disk !== 'public')
      return self::api("/appearances/$appearance_id/sprite", ['size' => $double ? self::SIZE_DOUBLE : self::SIZE_DEFAULT]);

    $has_double = $double && !empty((json_decode((string)$media->generated_conversions, true) ?: [])[self::DOUBLE_SIZE]);

    return self::cdnUrl().'/'.self::mediaPath((int)$media->id, self::SPRITES, $media->file_name, $has_double ? self::DOUBLE_SIZE : null);
  }

  /** Changes whenever the sprite is replaced (a new sprite is a new media record), like the hash of Luna's own API */
  public static function spriteHash(int $appearance_id):?string {
    $media = self::spriteMedia($appearance_id);

    return $media === null ? null : md5((string)$media->uuid);
  }
}
