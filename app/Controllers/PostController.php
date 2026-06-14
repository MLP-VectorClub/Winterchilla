<?php

namespace App\Controllers;

use App\CoreUtils;
use App\Models\LegacyPostMapping;
use App\Models\Post;
use App\ShowHelper;
use function intval;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="Post",
 *   type="object",
 *   description="Represents an art post (request or reservation)",
 *   required={"label"},
 *   additionalProperties=false,
 *   @OA\Property(property="label", type="string", nullable=true, description="Display label for the post"),
 *   @OA\Property(property="type", type="string", enum={"chr","obj","bg"}, description="Request type, only present for requests"),
 *   @OA\Property(property="reserved_at", type="string", format="date-time", description="Date the request was reserved, or an empty string if not set. Only present for developers viewing a reserved request"),
 *   @OA\Property(property="posted_at", type="string", format="date-time", description="Only present for developers"),
 *   @OA\Property(property="finished_at", type="string", format="date-time", description="Date the post was finished, or an empty string if not set. Only present for developers when the post is reserved and finished"),
 * )
 */
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
