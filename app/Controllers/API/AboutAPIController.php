<?php

namespace App\Controllers\API;

use App\CoreUtils;
use App\Models\User;
use App\Response;

/**
 * AboutAPIController
 */
class AboutAPIController extends APIController {
  static function mapGit(array $git) {
    return [
      'commitId' => $git['commit_id'],
      'commitTime' => date('c', strtotime($git['commit_time'])),
    ];
  }

  function connection() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $git = CoreUtils::getFooterGitInfoRaw();
    $git = empty($git) ? null : self::mapGit($git);

    Response::ok([
      'commitId' => $git['commitId'] ?? null,
      'commitTime' => $git['commitTime'] ?? null,
      'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
      'proxiedIps' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
      'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
  }

  function members() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $users = User::find('all', ['conditions' => ["role != 'user'"], 'order' => 'name asc']);

    Response::ok(array_map(fn(User $u) => UsersAPIController::mapPublicUser($u), $users));
  }

  public function upcoming() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    Response::ok(['html' => CoreUtils::getSidebarUpcoming(NOWRAP)]);
  }
}
