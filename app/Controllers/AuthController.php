<?php

namespace App\Controllers;

use App\Auth;
use App\Cookie;
use App\CoreUtils;
use App\DB;
use App\DeviantArt;
use App\HTTP;
use App\Models\FailedAuthAttempt;
use App\Models\User;
use App\Permission;
use App\Response;
use App\Twig;
use Exception;
use Monolog\Logger;
use OpenApi\Annotations as OA;

class AuthController extends Controller {
  public function begin() {
    $auth_url = DeviantArt::OAuthProviderInstance()->getAuthorizationUrl([
      'scope' => ['user'],
    ]);
    if (isset($_GET['return']) && CoreUtils::isURLSafe($_GET['return']))
      Auth::$session->setData('return_url', $_GET['return']);
    Auth::$session->setData('da_state', DeviantArt::OAuthProviderInstance()->getState());
    HTTP::softRedirect($auth_url, "Checking whether you're logged in");
  }

  public function softEnd() {
    $query_string = ltrim($_SERVER['QUERY_STRING'], '?');
    HTTP::softRedirect("/da-auth/end?$query_string", 'Creating session');
  }

  public function end() {
    if (!isset($_GET['error']) && (empty($_GET['code']) || empty($_GET['state']) || $_GET['state'] !== Auth::$session->pullData('da_state')))
      $_GET['error'] = 'unauthorized_client';
    if (isset($_GET['error'])){
      $err = $_GET['error'];
      $errdesc = $_GET['error_description'] ?? null;
      if (Auth::$signed_in)
        $this->_redirectBack();
      $this->_error($err, $errdesc);
    }

    if (FailedAuthAttempt::canAuthenticate()){
      try {
        $da_user = DeviantArt::exchangeForAccessToken($_GET['code']);
      }
      catch (Exception $e){
        CoreUtils::logError(__METHOD__.': '.$e->getMessage()."\n".$e->getTraceAsString());
        FailedAuthAttempt::record();
        $this->_error('server_error');
      }
      if (!empty($da_user)) {
        Auth::$signed_in = true;
        Auth::$user = $da_user->user;
      }
    }
    else {
      $_GET['error'] = 'time_out';
      $_GET['error_description'] = "You've made too many failed login attempts in a short period of time. Please wait a few minutes before trying again.";
    }

    if (isset($_GET['error'])){
      $err = $_GET['error'];
      if (isset($_GET['error_description'])){
        $errdesc = $_GET['error_description'];

        if ($err === 'user_banned')
          $errdesc .= "\n\nIf you'd like to appeal your ban, please <a class='send-feedback'>contact us</a>.";
      }
      if ($err !== 'time_out')
        FailedAuthAttempt::record();
      $this->_error($err, $errdesc ?? null);
    }

    if (Auth::$session->hasData('return_url'))
      $this->_redirectBack();

    Twig::display('login_confirm');
  }

  /**
   * @OA\Post(
   *   path="/da-auth/sign-out",
   *   description="Signs the current user out by deleting their session. If 'everywhere' is set, deletes all of the current user's sessions, or (with staff permission) all sessions belonging to a specified target user.",
   *   tags={"authentication"},
   *   security={},
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
   *     response="200",
   *     description="OK (also returned if the user wasn't signed in to begin with)",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="400",
   *     description="Could not remove session information from the database",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   ),
   *   @OA\Response(
   *     response="403",
   *     description="Insufficient permission to sign out another user's sessions, or that user doesn't exist",
   *     @OA\JsonContent(ref="#/components/schemas/ServerResponse")
   *   )
   * )
   */
  public function signOut() {
    if ($this->action !== 'POST')
      CoreUtils::notAllowed();

    if (!Auth::$signed_in)
      Response::success("You're not signed in");

    if (isset($_REQUEST['everywhere'])){
      $col = 'user_id';
      $val = Auth::$user->id;
      $user_id = $_REQUEST['user_id'] ?? null;
      if ($user_id !== null){
        if (Permission::insufficient('staff'))
          Response::fail();
        $target_user = User::find((int) $user_id);
        if (empty($target_user))
          Response::fail("Target user doesn't exist");
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
      Response::fail('Could not remove information from database');

    if (empty($target_user))
      Cookie::delete('access', Cookie::HTTP_ONLY);
    Response::done();
  }

  private function _error(?string $err, ?string $errdesc = null) {
    if ($err === 'unauthorized_client' && empty($_GET['code']))
      $this->_redirectBack();

    if ($err !== 'time_out' && $err !== 'server_error')
      CoreUtils::logError(rtrim("DeviantArt authentication error ($err): $errdesc", ': '), Logger::WARNING);

    HTTP::statusCode(403);
    CoreUtils::loadPage('ErrorController::auth', [
      'title' => 'DeviantArt authentication error',
      'js' => [true],
      'import' => [
        'err' => $err,
        'errdesc' => $errdesc,
      ],
    ]);
  }

  private function _redirectBack() {
    $return_url = Auth::$session->pullData('return_url');

    HTTP::tempRedirect($return_url ?? '/');
  }

  /**
   * @OA\Get(
   *   path="/da-auth/status",
   *   description="Checks the current sign-in/session status. If the DeviantArt access token has expired, this may trigger a background refresh and report that the session is updating.",
   *   tags={"authentication"},
   *   security={},
   *   @OA\Response(
   *     response="200",
   *     description="OK",
   *     @OA\JsonContent(
   *       allOf={
   *         @OA\Schema(ref="#/components/schemas/ServerResponse"),
   *         @OA\Schema(
   *           type="object",
   *           @OA\Property(property="updating", type="boolean", description="True if the session's DeviantArt access token is being refreshed in the background"),
   *           @OA\Property(property="retries_remaining", type="integer", description="Number of refresh attempts remaining before an immediate refresh is forced (only present while updating)"),
   *           @OA\Property(property="deleted", type="boolean", description="True if the user is no longer signed in"),
   *           @OA\Property(property="loggedIn", type="boolean", description="Whether the sidebar should display the user as logged in")
   *         )
   *       }
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
        Response::done(['updating' => true, 'retries_remaining' => $force_threshold - $attempt_number]);
      }

      DeviantArt::gracefullyRefreshAccessTokenImmediately();
    }

    Auth::$session->unsetData('refresh_attempts');

    Response::done([
      'deleted' => !Auth::$signed_in,
      'loggedIn' => CoreUtils::getSidebarLoggedIn(),
    ]);
  }
}
