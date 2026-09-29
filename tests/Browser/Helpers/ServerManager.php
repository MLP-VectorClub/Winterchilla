<?php

namespace Tests\Browser\Helpers;

class ServerManager {
  private static ?int $pid = null;
  /** @var resource|null Kept alive so proc_open doesn't kill the child */
  private static $procHandle = null;

  public static function start(): void {
    if (self::$pid !== null)
      return;

    $docRoot = dirname(__DIR__, 3) . '/public';
    $host    = '127.0.0.1:8765';

    // A server orphaned by an aborted earlier run would pass waitUntilReady() below and silently
    // serve this run instead of ours, so refuse to start rather than reuse it
    $probe = @fsockopen('127.0.0.1', 8765, $errno, $errstr, 0.5);
    if ($probe !== false) {
      fclose($probe);
      throw new \RuntimeException("Something is already listening on $host (likely a test server left over from an aborted run) — stop it first, e.g. `fuser -k 8765/tcp`");
    }

    $pid = pcntl_fork();

    if ($pid === -1) {
      self::startViaProc($docRoot, $host);
      return;
    }

    if ($pid === 0) {
      // Child: become the PHP built-in server
      pcntl_exec(PHP_BINARY, ['-S', $host, '-t', $docRoot]);
      exit(1);
    }

    self::$pid = $pid;
    self::registerShutdown();
    self::waitUntilReady(TestSeederConstants::BASE_URL);
  }

  /**
   * The afterAll() hook in tests/Browser/Pest.php does not reliably fire under Pest 4, which left
   * the server orphaned after every run — stop it when the test process exits instead.
   */
  private static function registerShutdown(): void {
    register_shutdown_function([self::class, 'stop']);
  }

  private static function startViaProc(string $docRoot, string $host): void {
    $cmd = sprintf('%s -S %s -t %s', PHP_BINARY, $host, escapeshellarg($docRoot));
    // Store handle as a static property — if it goes out of scope PHP kills the child
    self::$procHandle = proc_open($cmd, [], $pipes);
    if (self::$procHandle === false)
      throw new \RuntimeException('Failed to start PHP built-in server');

    $status = proc_get_status(self::$procHandle);
    self::$pid = $status['pid'];
    self::registerShutdown();
    self::waitUntilReady(TestSeederConstants::BASE_URL);
  }

  private static function waitUntilReady(string $url, int $maxAttempts = 30): void {
    for ($i = 0; $i < $maxAttempts; $i++) {
      $ctx = @stream_context_create(['http' => ['timeout' => 1]]);
      if (@file_get_contents($url . '/', false, $ctx) !== false)
        return;
      usleep(300_000);
    }
    throw new \RuntimeException("PHP built-in server at $url did not start in time");
  }

  public static function stop(): void {
    if (self::$pid !== null) {
      posix_kill(self::$pid, SIGTERM);
      self::$pid = null;
    }
    if (self::$procHandle !== null) {
      proc_close(self::$procHandle);
      self::$procHandle = null;
    }
  }
}
