<?php

namespace App\Controllers\API;

use App\Auth;
use App\CoreUtils;
use App\Models\Notification;
use App\Notifications;
use App\Response;
use Throwable;

class NotificationAPIController extends APIController {
  public function __construct() {
    parent::__construct();

    if (!Auth::$signed_in)
      Response::error(401);
  }

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
