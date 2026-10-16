<?php

namespace App\Controllers\API;

use App\Auth;
use App\Cookie;
use App\CoreUtils;
use App\DB;
use App\DeviantArt;
use App\Models\User;
use App\Permission;
use App\Response;

class AuthAPIController extends APIController {
  public function signOut() {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (!Auth::$signed_in)
      Response::noContent();

    if (isset($_REQUEST['everywhere'])){
      $col = 'user_id';
      $val = Auth::$user->id;
      $user_id = $_REQUEST['userId'] ?? null;
      if ($user_id !== null){
        if (Permission::insufficient('staff'))
          Response::error(403);
        $target_user = User::find((int) $user_id);
        if (empty($target_user))
          Response::error(404, "Target user doesn't exist");
        if ($target_user->id !== Auth::$user->id)
          $val = $target_user->id;
        else unset($target_user);
      }
    }
    else {
      $col = 'id';
      $val = Auth::$session->id;
    }

    if (!DB::$instance->where($col, $val)->delete('sessions'))
      Response::error(500, 'Could not remove information from database');

    if (empty($target_user))
      Cookie::delete('access', Cookie::HTTP_ONLY);
    Response::noContent();
  }

  public function sessionStatus() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    if (Auth::$signed_in && Auth::$session->updating){
      $attempt_number = Auth::$session->getData('refresh_attempts', 0);
      $force_threshold = 5;
      $immediate_refresh = $attempt_number >= $force_threshold && Auth::$session->expired;
      if (!$immediate_refresh) {
        Auth::$session->setData('refresh_attempts', $attempt_number + 1);
        Response::ok(['updating' => true, 'retriesRemaining' => $force_threshold - $attempt_number]);
      }

      DeviantArt::gracefullyRefreshAccessTokenImmediately();
    }

    if (Auth::$signed_in)
      Auth::$session->unsetData('refresh_attempts');

    Response::ok([
      'deleted' => !Auth::$signed_in,
      'loggedIn' => CoreUtils::getSidebarLoggedIn(),
    ]);
  }
}
