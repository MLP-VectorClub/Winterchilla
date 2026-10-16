<?php

namespace App\Controllers;

use App\CoreUtils;
use App\Models\LegacyPostMapping;
use App\Models\Post;
use App\ShowHelper;
use function intval;

class PostController extends Controller {
  public const SHARE_TYPE = [
    'req' => 'request',
    'res' => 'reservation',
  ];

  public function share($params) {
    if (!empty($params['thing'])){
      if (!array_key_exists($params['thing'], self::SHARE_TYPE))
        CoreUtils::notFound();

      $type = self::SHARE_TYPE[$params['thing']];
      $old_id = (int)$params['id'];
      $linked_post = LegacyPostMapping::lookup($old_id, $type);
    }
    else {
      $id = intval($params['id'], 36);

      if ($id > POSTGRES_INTEGER_MAX || $id < 1)
        CoreUtils::notFound();

      $linked_post = Post::find($id);
    }

    if ($linked_post === NULL)
      CoreUtils::notFound();

    ShowHelper::loadPage($linked_post->show, $linked_post);
  }

}
