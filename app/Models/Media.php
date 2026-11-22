<?php

declare(strict_types=1);

namespace App\Models;

/**
 * A row of Luna's `media` table (Spatie MediaLibrary): where Luna keeps appearance sprites and cutie marks. Only ever read here.
 *
 * @property int    $id
 * @property string $model_type
 * @property int    $model_id
 * @property string $uuid
 * @property string $collection_name
 * @property string $file_name
 * @property string $disk                'public' (served by the CDN) or 'local' (private appearances, only served by Luna's API)
 * @property string $generated_conversions JSON object, e.g. {"2x":true}
 */
class Media extends NSModel {
  public static $table_name = 'media';
}
