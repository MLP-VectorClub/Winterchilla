<?php

namespace Tests\Browser\Helpers;

class ServerManager {
  private static ?int $pid = null;

  public static function start(): void {
    if (self::$pid !== null)
      return;

    $docRoot = dirname(__DIR__, 3) . '/public';
    $host    = '127.0.0.1:8765';

    $pid = pcntl_fork();

    if ($pid === -1) {
      // pcntl_fork not available — fall back to proc_open
      self::startViaProc($docRoot, $host);
      return;
    }

    if ($pid === 0) {
      // Child process: exec the PHP server
      pcntl_exec(PHP_BINARY, ['-S', $host, '-t', $docRoot]);
      exit(1);
    }

    self::$pid = $pid;
    self::waitUntilReady(TestSeederConstants::BASE_URL);
  }

  private static function startViaProc(string $docRoot, string $host): void {
    $cmd = sprintf('%s -S %s -t %s', PHP_BINARY, $host, escapeshellarg($docRoot));
    $handle = proc_open($cmd, [], $pipes);
    if ($handle === false)
      throw new \RuntimeException('Failed to start PHP built-in server');

    $status = proc_get_status($handle);
    self::$pid = $status['pid'];
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
  }
}
