<?php

namespace App;

use App\Models\Notification;
use Exception;

class Notifications
{
  public const
    ALL = 0,
    UNREAD_ONLY = 1,
    READ_ONLY = 2;

  /**
   * Gets a list of notifications for the current user
   *
   * @param int $only Expects self::UNREAD_ONLY or self::READ_ONLY
   *
   * @return Notification[]
   */
  public static function get($only = self::ALL)
  {
    if (!Auth::$signed_in)
      return null;
    $user_id = Auth::$user->id;

    switch ($only) {
      case self::UNREAD_ONLY:
        DB::$instance->where('read_at IS NULL');
        break;
      case self::READ_ONLY:
        DB::$instance->where('read_at IS NOT NULL');
        break;
    }

    return DB::$instance->where('recipient_id', $user_id)->get('notifications');
  }

  /**
   * @param Notification[] $Notifications
   * @param bool $wrap
   *
   * @return string
   */
  public static function getHTML($Notifications, bool $wrap = WRAP): string
  {
    return Twig::$env->render('notifications/_list.html.twig', [
      'notifications' => $Notifications,
      'wrap' => $wrap,
    ]);
  }
}
