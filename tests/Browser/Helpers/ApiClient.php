<?php

namespace Tests\Browser\Helpers;

/**
 * Minimal HTTP client for the API contract tests. By default it talks to Winterchilla's test server, keeps a cookie jar
 * (session + CSRF token) and echoes the CSRF_TOKEN cookie back on state-changing requests, like the site's own JS does.
 *
 * The contract tests can be pointed at another implementation of the API (Luna) through the environment:
 *  - CONTRACT_BASE_URL   origin of the server under test (default: Winterchilla's test server)
 *  - CONTRACT_API_PATH   API prefix (default: /api/v0; set it to an empty string when the API is served at the root)
 *  - CONTRACT_AUTH       `cookie` (default) or `bearer`; bearer mode sends `Authorization: Bearer <token>` and no CSRF token
 *  - CONTRACT_LOGIN_URL  where loggedInAs() logs in, `{id}` is the seeded user ID (default: /test-login/{id}); in bearer mode the
 *                        endpoint must answer JSON with a `token` key (e.g. a test-only POST /test/login/{id})
 *  - CONTRACT_LOGIN_METHOD  HTTP method of the login request (default: GET for cookies, POST for bearer)
 * With CONTRACT_BASE_URL set, the browser suite's bootstrap neither resets the database nor starts Winterchilla's server.
 */
class ApiClient {
  private string $jar;
  private ?string $token = null;

  public static function external():bool {
    return getenv('CONTRACT_BASE_URL') !== false && getenv('CONTRACT_BASE_URL') !== '';
  }

  public static function baseUrl():string {
    return rtrim(self::external() ? getenv('CONTRACT_BASE_URL') : TestSeederConstants::BASE_URL, '/');
  }

  public static function apiPath():string {
    $path = getenv('CONTRACT_API_PATH');

    // An empty value is allowed: an implementation that serves the API at the root has no prefix
    return $path === false ? TestSeederConstants::API_PATH : $path;
  }

  private static function bearer():bool {
    return getenv('CONTRACT_AUTH') === 'bearer';
  }

  public function __construct() {
    $this->jar = tempnam(sys_get_temp_dir(), 'apijar');
  }

  public function __destruct() {
    @unlink($this->jar);
  }

  public static function guest():self {
    return new self();
  }

  public static function loggedInAs(int $userId):self {
    $client = new self();
    $login = str_replace('{id}', (string)$userId, getenv('CONTRACT_LOGIN_URL') ?: '/test-login/{id}');
    if (self::bearer()) {
      $method = getenv('CONTRACT_LOGIN_METHOD') ?: 'POST';
      $client->token = $client->raw($method, $login, accept: 'application/json')['json']['token'] ?? null;
      if ($client->token === null)
        throw new \RuntimeException("The login endpoint $login did not return a token");
    }
    else {
      // Sets the session cookie and redirects; we only need the cookie
      $client->raw(getenv('CONTRACT_LOGIN_METHOD') ?: 'GET', $login, followRedirects: false);
    }
    return $client;
  }

  /**
   * @return array{status: int, body: string, json: mixed, contentType: ?string}
   */
  public function request(string $method, string $path, array $params = []):array {
    return $this->raw($method, self::apiPath() . $path, $params);
  }

  public function get(string $path, array $query = []):array {
    return $this->request('GET', $path, $query);
  }

  /** Sends the data as an `application/json` body, the way a fetch()-based front end would. */
  public function json(string $method, string $path, array $data = []):array {
    if (!self::bearer()) {
      if ($this->cookie('CSRF_TOKEN') === null)
        $this->raw('GET', self::apiPath() . '/users/session/status');
      $data['CSRF_TOKEN'] = $this->cookie('CSRF_TOKEN') ?? '';
    }

    return $this->raw($method, self::apiPath() . $path, [], rawBody: json_encode($data), contentType: 'application/json');
  }

  public function post(string $path, array $data = []):array {
    return $this->request('POST', $path, $data);
  }

  /** Fetches a regular (non-API) page as this client, e.g. to read data rendered into the HTML. */
  public function page(string $path):string {
    return $this->raw('GET', $path, accept: 'text/html')['body'];
  }

  /**
   * POSTs a multipart form with one uploaded file, e.g. a sprite image.
   */
  public function upload(string $path, string $field, string $file, array $data = [], string $mime = 'image/png'):array {
    $data[$field] = new \CURLFile($file, $mime, basename($file));
    return $this->raw('POST', self::apiPath() . $path, $data, multipart: true);
  }

  private function raw(string $method, string $path, array $params = [], bool $followRedirects = true, string $accept = 'application/json', bool $multipart = false, ?string $rawBody = null, ?string $contentType = null):array {
    $url = self::baseUrl() . $path;
    $ch = curl_init();
    $opts = [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => $followRedirects,
      CURLOPT_COOKIEJAR => $this->jar,
      CURLOPT_COOKIEFILE => $this->jar,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => array_filter(['Accept: ' . $accept, $this->token !== null ? 'Authorization: Bearer ' . $this->token : null]),
    ];
    if ($rawBody !== null) {
      $opts[CURLOPT_POSTFIELDS] = $rawBody;
      $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: ' . $contentType;
    }
    elseif ($method !== 'GET') {
      if (!self::bearer()) {
        // The server hands out the CSRF cookie on any response; make sure we have one before writing
        if ($this->cookie('CSRF_TOKEN') === null)
          $this->raw('GET', self::apiPath() . '/users/session/status');
        $params['CSRF_TOKEN'] = $this->cookie('CSRF_TOKEN') ?? '';
      }
      // An array (with CURLFile values) is sent as multipart/form-data, a string as urlencoded
      $opts[CURLOPT_POSTFIELDS] = $multipart ? $params : http_build_query($params);
    }
    elseif (!empty($params))
      $url .= '?' . http_build_query($params);
    $opts[CURLOPT_URL] = $url;
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $result = [
      'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
      'body' => (string)$body,
      'json' => json_decode((string)$body, true),
      'contentType' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
    ];
    return $result;
  }

  private function cookie(string $name):?string {
    foreach (file($this->jar, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
      $line = preg_replace('/^#HttpOnly_/', '', $line);
      $cols = explode("\t", $line);
      if (count($cols) >= 7 && $cols[5] === $name)
        return urldecode($cols[6]);
    }
    return null;
  }
}
