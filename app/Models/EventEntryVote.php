<?php

namespace App\Models;

use ActiveRecord\DateTime;
use App\Time;

/**
 * @property int            $entry_id
 * @property string         $user_id
 * @property int            $value
 * @property DateTime       $cast_at
 * @property DeviantartUser $user     (Via relations)
 * @property EventEntry     $entry    (Via relations)
 * @method static EventEntryVote find_by_entry_id_and_user_id(int $entr_yid, string $user_id)
 */
class EventEntryVote extends NSModel {
  public static $table_name = 'event_entry_votes';

  public static $primary_key = ['entry_id', 'user_id'];

  public static $belongs_to = [
    ['user', 'class' => 'DeviantartUser', 'foreign_key' => 'user_id'],
    ['entry', 'class' => 'EventEntry'],
  ];

  /**
   * Checks if the vote is locked in, requires the event's last edit timestamp
   *
   * @param EventEntry $entry
   * @param int|null   $now
   *
   * @return bool
   */
  public function isLockedIn(EventEntry $entry, ?int $now = null):bool {
    $entryEditTS = $entry->updated_at->getTimestamp();
    $voteCastTS = $this->cast_at->getTimestamp();

    return ($now ?? time()) - $voteCastTS >= Time::IN_SECONDS['hour'] && $entryEditTS < $voteCastTS;
  }
}
