<?php

namespace Tests\Browser\Helpers;

class TestSeederConstants {
  public const BASE_URL = 'http://127.0.0.1:8765';
  public const USER_ID = 9001;
  public const ADMIN_ID = 9002;
  public const USER_DA_ID = '0f0e0d0c-0b0a-4000-8000-000000009001';
  public const ADMIN_DA_ID = '0f0e0d0c-0b0a-4000-8000-000000009002';
  public const APPEARANCE_ID = 1;
  // The regular user's personal guide: a public and a private appearance, the public one with a color group
  public const PERSONAL_APPEARANCE_ID = 3;
  public const PRIVATE_PERSONAL_APPEARANCE_ID = 4;
  public const PERSONAL_COLOR_GROUP_ID = 1;
  // Lets anyone view the private appearance when passed as ?token=
  public const PRIVATE_PERSONAL_TOKEN = '0f0e0d0c-0b0a-4000-8000-00000000f004';
  // Deliberately high: fs/ is shared with the dev environment, so a low ID could clobber a real cm_source file
  public const CUTIEMARK_ID = 900001;
  // An appearance (with a cutie mark and its files on disk) that exists only to be deleted
  public const DELETABLE_APPEARANCE_ID = 2;
  public const DELETABLE_CUTIEMARK_ID = 900002;
  public const SHOW_ID = 1;
  public const MOVIE_ID = 2;
  public const EVENT_ID = 1;
  public const POST_ID = 1;
  // Requests by USER_ID: 2 exists to be deleted, 3 is reserved by ADMIN_ID
  public const DELETABLE_POST_ID = 2;
  public const RESERVED_POST_ID = 3;
  // A broken request (usable images) for the unbreak test
  public const BROKEN_POST_ID = 4;
  // Another broken request, cleared through the UI test
  public const BROKEN_UI_POST_ID = 5;
  // Unread notifications: 1 and 2 belong to USER_ID, 3 to ADMIN_ID
  public const NOTIFICATION_ID = 1;
  public const NOTIFICATION_MARK_READ_ID = 2;
  public const ADMIN_NOTIFICATION_ID = 3;
  // Event entries: 1 and 3 belong to USER_ID (3 is for deletion), 2 to ADMIN_ID
  public const EVENT_ENTRY_ID = 1;
  public const ADMIN_EVENT_ENTRY_ID = 2;
  public const EVENT_ENTRY_DELETE_ID = 3;
  // Belongs to USER_ID and is withdrawn through the UI test
  public const EVENT_ENTRY_UI_DELETE_ID = 4;
  public const API_PATH = '/api/v0';
}
