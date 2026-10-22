<?php

require __DIR__.'/../config/init.php';

use App\CoreUtils;
use App\RouteHelper;

// Strip &hellip; and what comes after
$decoded_uri = CoreUtils::trim(urldecode($_SERVER['REQUEST_URI']));
$request_uri = preg_replace('/(?:….*|<)$/u', '', $decoded_uri);
// Strip backslash
$request_uri = str_replace('\\', '', $request_uri);
// Strip non-ascii
$safe_uri = preg_replace('/[^ -~]/', '', $request_uri);
// Enforce URL
CoreUtils::fixPath($safe_uri);

require CONFPATH.'routes/index.php';
/** @var $router AltoRouter */
/** @var $match array */
// HEAD is answered by the handler of GET (the web server drops the body)
$request_method = $_SERVER['REQUEST_METHOD'] === 'HEAD' ? 'GET' : $_SERVER['REQUEST_METHOD'];
$match = $router->match($safe_uri, $request_method);
if (!isset($match['target'])){
  // The path exists, only not for this method
  $allowed = array_values(array_filter(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], fn($method) => $method !== $request_method && isset($router->match($safe_uri, $method)['target'])));
  if (!empty($allowed)){
    if (in_array('GET', $allowed, true))
      $allowed[] = 'HEAD';
    CoreUtils::notAllowed($allowed);
  }
  CoreUtils::notFound();
}

// Read-only mode: nothing that changes data is accepted (the test-only routes of the browser tests are the exception). Signing in writes a session
// and, for a new member, an account, so the start and the return addresses of the sign in providers are refused too
if (CoreUtils::readOnly() && !str_starts_with($safe_uri, '/test-')){
  $sign_in_return = preg_match('~^/(da-auth|discord-connect)/(begin|end)(\?|$)~', $safe_uri) === 1;
  if ($sign_in_return || !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'OPTIONS'], true)){
    header('Retry-After: 3600');
    if (CoreUtils::isJSONExpected())
      \App\Response::error(503, 'This site is read-only now, nothing can be changed here anymore.', ['readOnly' => true]);
    fatal_error('readonly');
  }
}

RouteHelper::processHandler($match['target'], $match['params']);

