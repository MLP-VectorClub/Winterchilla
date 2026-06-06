<?php

namespace App\Controllers;

use App\CoreUtils;
use App\HTTP;
use App\Models\Session;
use App\Models\User;

class TestController extends Controller {
  protected static $auth = false;

  public function __construct() {
    parent::__construct();
    if (!CoreUtils::env('TEST_MODE'))
      CoreUtils::notFound();
  }

  public function dialogPage(): void {
    CoreUtils::checkNutshell();
    CoreUtils::loadPage(__METHOD__, [
      'title'   => 'Dialog Test Page',
      'noindex' => true,
    ]);
  }

  public function loginAs(array $params): void {
    $user = User::find((int)($params['user_id'] ?? 0));
    if ($user === null)
      HTTP::statusCode(404, AND_DIE);

    Session::delete_all(['conditions' => [
      "user_id = ? AND browser_name = 'Playwright-Test'",
      $user->id,
    ]]);

    $cookie = Session::generateCookie();
    Session::create([
      'user_id'      => $user->id,
      'token'        => CoreUtils::sha256($cookie),
      'browser_name' => 'Playwright-Test',
      'platform'     => 'Test',
      'last_visit'   => date('c'),
    ]);
    Session::setCookie($cookie);

    HTTP::tempRedirect('/');
  }
}
