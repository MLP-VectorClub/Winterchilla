<?php

namespace Tests\Browser\Helpers;

class TestSeederConstants {
  public const BASE_URL = 'http://127.0.0.1:8765';
  public const USER_ID = 9001;
  public const ADMIN_ID = 9002;
  public const APPEARANCE_ID = 1;
  // Deliberately high: fs/ is shared with the dev environment, so a low ID could clobber a real cm_source file
  public const CUTIEMARK_ID = 900001;
  public const SHOW_ID = 1;
  public const MOVIE_ID = 2;
  public const EVENT_ID = 1;
}
