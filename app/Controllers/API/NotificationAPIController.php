<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\Models\Notification;
use App\Notifications;
use App\Response;
use OpenApi\Annotations as OA;
use Throwable;

class NotificationAPIController extends APIController {
  public function __construct() {
    parent::__construct();

    if (!Auth::$signed_in)
      Response::error(401);
  }

  /**
   * @OA\Get(
   *   path="/notif",
   *   description="Get a rendered HTML list of the current user's unread notifications. Requires authentication",
   *   tags={"notifications"},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       required={"list"},
   *       additionalProperties=false,
   *       @OA\Property(property="list", type="string", description="Rendered HTML for the notification list")
   *     )
   *   ),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="500", description="The notifications could not be fetched", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function get() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    try {
      $notifs = Notifications::getHTML(Notifications::get(Notifications::UNREAD_ONLY), NOWRAP);
      Response::ok(['list' => $notifs]);
    }
    catch (Throwable $e){
      CoreUtils::logError('Exception caught when fetching notifications: '.$e->getMessage()."\n".$e->getTraceAsString());
      Response::error(500, 'An error prevented the notifications from appearing.');
    }
  }

  /**
   * @OA\Post(
   *   path="/notif/{id}/mark-read",
   *   description="Mark one of the current user's notifications as read. Requires authentication",
   *   tags={"notifications"},
   *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(ref="#/components/schemas/OneBasedId")),
   *   @OA\Response(response="204", description="Marked as read"),
   *   @OA\Response(response="401", description="Not signed in", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *   @OA\Response(response="404", description="Notification does not exist or does not belong to the current user", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function markRead($params) {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    $nid = (int)$params['id'];
    $notif = Notification::find($nid);
    if (empty($notif) || $notif->recipient_id !== Auth::$user->id)
      Response::error(404, "The notification (#$nid) does not exist");

    $notif->safeMarkRead();

    Response::noContent();
  }
}
