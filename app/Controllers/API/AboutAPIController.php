<?php

namespace App\Controllers\API;

use App\CoreUtils;
use App\Models\User;
use App\Response;

/**
 * AboutAPIController
 */
class AboutAPIController extends APIController {
  /**
   * @OA\Schema(
   *   schema="GitInfo",
   *   type="object",
   *   description="Contains information about the server's current revision",
   *   required={
   *     "commitId",
   *     "commitTime",
   *   },
   *   additionalProperties=false,
   *   @OA\Property(
   *     property="commitId",
   *     type="string",
   *     example="a1bfc6d"
   *   ),
   *   @OA\Property(
   *     property="commitTime",
   *     type="string",
   *     format="date-time"
   *   )
   * )
   * @param array $git
   *
   * @return array
   */
  static function mapGit(array $git) {
    return [
      'commitId' => $git['commit_id'],
      'commitTime' => date('c', strtotime($git['commit_time'])),
    ];
  }

  /**
   * @OA\Get(
   *   path="/about/connection",
   *   description="Information about the server build and about the connection the request came from",
   *   security={},
   *   tags={"server info"},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"commitId", "commitTime", "ip", "proxiedIps", "userAgent"},
   *       additionalProperties=false,
   *       @OA\Property(property="commitId", type="string", nullable=true, description="Short hash of the deployed commit"),
   *       @OA\Property(property="commitTime", type="string", format="date-time", nullable=true),
   *       @OA\Property(property="ip", type="string", nullable=true),
   *       @OA\Property(property="proxiedIps", type="string", nullable=true, description="The X-Forwarded-For header, if any"),
   *       @OA\Property(property="userAgent", type="string", nullable=true)
   *     )
   *   )
   * )
   */
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

  /**
   * @OA\Get(
   *   path="/about/members",
   *   description="The staff and other non-regular members of the club, ordered by name",
   *   security={},
   *   tags={"server info"},
   *   @OA\Response(response="200", description="OK", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User")))
   * )
   */
  function members() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    $users = User::find('all', ['conditions' => ["role != 'user'"], 'order' => 'name asc']);

    Response::ok(array_map(fn(User $u) => UsersAPIController::mapPublicUser($u), $users));
  }

  /**
   * @OA\Get(
   *   path="/about/upcoming",
   *   description="Returns rendered HTML for the sidebar's list of upcoming episodes/events",
   *   tags={"server info"},
   *   security={},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       required={"html"},
   *       @OA\Property(property="html", type="string", description="Rendered upcoming items HTML")
   *     )
   *   )
   * )
   */
  public function upcoming() {
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    Response::ok(['html' => CoreUtils::getSidebarUpcoming(NOWRAP)]);
  }
}
