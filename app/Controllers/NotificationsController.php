<?php

namespace App\Controllers;

use App\Appearances;
use App\Auth;
use App\CoreUtils;
use App\Input;
use App\JSON;
use App\Models\Appearance;
use App\Models\Notification;
use App\Notifications;
use App\Response;
use Throwable;

class NotificationsController extends Controller {
  public function __construct() {
    parent::__construct();

    if (!Auth::$signed_in)
      Response::fail();
  }

  public function get() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    try {
      $notifs = Notifications::getHTML(Notifications::get(Notifications::UNREAD_ONLY), NOWRAP);
      Response::done(['list' => $notifs]);
    }
    catch (Throwable $e){
      CoreUtils::logError('Exception caught when fetching notifications: '.$e->getMessage()."\n".$e->getTraceAsString());
      Response::fail('An error prevented the notifications from appearing. If this persists, <a class="send-feedback">let us know</a>.');
    }
  }

  public function markRead($params) {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    $nid = (int)$params['id'];
    $notif = Notification::find($nid);
    if (empty($notif) || $notif->recipient_id !== Auth::$user->id)
      Response::fail("The notification (#$nid) does not exist");

    $notif->safeMarkRead();

    Response::done();
  }
}
