<?php

namespace Tests\Browser\Helpers;

class TestSeederConstants {
  public const BASE_URL = 'http://127.0.0.1:8765';
  public const USER_ID = 9001;
  public const ADMIN_ID = 9002;
  public const USER_DA_ID = '0f0e0d0c-0b0a-4000-8000-000000009001';
  public const ADMIN_DA_ID = '0f0e0d0c-0b0a-4000-8000-000000009002';
  public const APPEARANCE_ID = 1;
  // Deliberately high: fs/ is shared with the dev environment, so a low ID could clobber a real cm_source file
  public const CUTIEMARK_ID = 900001;
  // An appearance (with a cutie mark and its files on disk) that exists only to be deleted
  public const DELETABLE_APPEARANCE_ID = 2;
  public const DELETABLE_CUTIEMARK_ID = 900002;
  public const SHOW_ID = 1;
  public const MOVIE_ID = 2;
  public const EVENT_ID = 1;
  public const POST_ID = 1;
  // Unread notifications: 1 and 2 belong to USER_ID, 3 to ADMIN_ID
  public const NOTIFICATION_ID = 1;
  public const NOTIFICATION_MARK_READ_ID = 2;
  public const ADMIN_NOTIFICATION_ID = 3;
  // Event entries: 1 and 3 belong to USER_ID (3 is for deletion), 2 to ADMIN_ID
  public const EVENT_ENTRY_ID = 1;
  public const ADMIN_EVENT_ENTRY_ID = 2;
  public const EVENT_ENTRY_DELETE_ID = 3;
  public const API_PATH = '/api/v0';
}
