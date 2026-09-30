<?php

namespace Tests\Browser\Helpers;

/**
 * Minimal HTTP client for the API contract tests. Keeps a cookie jar (session + CSRF token) and echoes the
 * CSRF_TOKEN cookie back on state-changing requests, like the site's own JS does.
 */
class ApiClient {
  private string $jar;

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
    // Sets the session cookie and redirects; we only need the cookie
    $client->raw('GET', '/test-login/' . $userId, followRedirects: false);
    return $client;
  }

  /**
   * @return array{status: int, body: string, json: mixed, contentType: ?string}
   */
  public function request(string $method, string $path, array $params = []):array {
    return $this->raw($method, TestSeederConstants::API_PATH . $path, $params);
  }

  public function get(string $path, array $query = []):array {
    return $this->request('GET', $path, $query);
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
    return $this->raw('POST', TestSeederConstants::API_PATH . $path, $data, multipart: true);
  }

  private function raw(string $method, string $path, array $params = [], bool $followRedirects = true, string $accept = 'application/json', bool $multipart = false):array {
    $url = TestSeederConstants::BASE_URL . $path;
    $ch = curl_init();
    $opts = [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => $followRedirects,
      CURLOPT_COOKIEJAR => $this->jar,
      CURLOPT_COOKIEFILE => $this->jar,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => ['Accept: ' . $accept],
    ];
    if ($method !== 'GET') {
      // The server hands out the CSRF cookie on any response; make sure we have one before writing
      if ($this->cookie('CSRF_TOKEN') === null)
        $this->raw('GET', TestSeederConstants::API_PATH . '/users/session/status');
      $params['CSRF_TOKEN'] = $this->cookie('CSRF_TOKEN') ?? '';
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
