<?php

namespace App\Controllers;

use App\Auth;
use App\CoreUtils;
use App\DeviantArt;
use App\HTTP;
use App\Models\FailedAuthAttempt;
use App\Twig;
use Exception;
use Monolog\Logger;

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
}
