<?php

namespace Models;

use ActiveRecord\DateTime;
use App\Models\EventEntry;
use App\Models\EventEntryVote;
use PHPUnit\Framework\TestCase;

class EventEntryVoteTest extends TestCase {
  private static function makeEntry(string $updatedAt): EventEntry {
    $entry = new EventEntry();
    $entry->assign_attribute('updated_at', new DateTime($updatedAt));
    return $entry;
  }

  private static function makeVote(string $createdAt): EventEntryVote {
    $vote = new EventEntryVote();
    $vote->assign_attribute('created_at', new DateTime($createdAt));
    return $vote;
  }

  public function testIsLockedIn() {
    $entry = self::makeEntry('2017-01-20T10:00:00Z');
    $entryVote = self::makeVote('2017-01-20T12:00:00Z');
    $lockedIn = $entryVote->isLockedIn($entry, strtotime('+59 minutes', $entryVote->created_at->getTimestamp()));
    self::assertEquals(false, $lockedIn, "Votes shouldn't be locked in after an hour if the entry isn't edited");
    $lockedIn = $entryVote->isLockedIn($entry, strtotime('+1 hour', $entryVote->created_at->getTimestamp()));
    self::assertEquals(true, $lockedIn, 'Votes should be locked in after an hour if the entry isn\'t edited');
    $lockedIn = $entryVote->isLockedIn($entry, strtotime('+1 day', $entryVote->created_at->getTimestamp()));
    self::assertEquals(true, $lockedIn, 'Votes should be locked in after an hour if the entry isn\'t edited');

    $entry = self::makeEntry('2017-01-20T15:40:00Z');
    // Simulates that vote was cast 20 minutes before the post was edited
    $entryVote = self::makeVote(date('c', strtotime('-20 minutes', $entry->updated_at->getTimestamp())));
    // Forcibly check if the post is locked in 10 minutes after the edit
    $lockedIn = $entryVote->isLockedIn($entry, strtotime('+10 minutes', $entry->updated_at->getTimestamp()));
    self::assertEquals(false, $lockedIn, 'Votes should be changeable if the entry is edited after the vote is cast');
  }
}
