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
use OpenApi\Annotations as OA;

class AuthAPIController extends APIController {
  /**
   * @OA\Post(
   *   path="/da-auth/sign-out",
   *   description="Signs the current user out by deleting their session. If 'everywhere' is set, deletes all of the current user's sessions, or (with staff permission) all sessions belonging to a specified target user. If no session cookie is present, this is a no-op that still returns a success response.",
   *   tags={"authentication"},
   *   security={
   *     {"SessionCookie": {}},
   *     {}
   *   },
   *   @OA\RequestBody(
   *     required=false,
   *     @OA\MediaType(
   *       mediaType="application/x-www-form-urlencoded",
   *       @OA\Schema(
   *         @OA\Property(property="everywhere", type="string", description="If present, signs out of all sessions instead of just the current one"),
   *         @OA\Property(property="user_id", ref="#/components/schemas/OneBasedId", description="When 'everywhere' is set, the ID of another user whose sessions should be deleted instead of the current user's. Requires staff permission.")
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *     response="204",
   *     description="Signed out (also returned if the user wasn't signed in to begin with)"
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission to sign out another user's sessions",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="404",
   *     description="The specified target user doesn't exist",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   ),
   *   @OA\Response(
   *     response="500",
   *     description="Could not remove session information from the database",
   *     @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
   *   )
   * )
   */
  public function signOut() {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (!Auth::$signed_in)
      Response::noContent();

    if (isset($_REQUEST['everywhere'])){
      $col = 'user_id';
      $val = Auth::$user->id;
      $user_id = $_REQUEST['user_id'] ?? null;
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

  /**
   * @OA\Get(
   *   path="/da-auth/status",
   *   description="Checks the current sign-in/session status. If the DeviantArt access token has expired, this may trigger a background refresh and report that the session is updating. If no session cookie is present, this reports a logged-out status.",
   *   tags={"authentication"},
   *   security={
   *     {"SessionCookie": {}},
   *     {}
   *   },
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="updating", type="boolean", description="True if the session's DeviantArt access token is being refreshed in the background"),
   *       @OA\Property(property="retriesRemaining", type="integer", description="Number of refresh attempts remaining before an immediate refresh is forced (only present while updating)"),
   *       @OA\Property(property="deleted", type="boolean", description="True if the user is no longer signed in"),
   *       @OA\Property(property="loggedIn", type="string", description="Sidebar HTML for the signed-in state")
   *     )
   *   )
   * )
   */
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
