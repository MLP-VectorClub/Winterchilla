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

    // variables_order=EGPCS exposes the environment below via $_ENV, where it takes precedence over .env
    // (Symfony Dotenv never overwrites variables that are already set)
    // opcache.revalidate_freq=0: a CLI opcache (if enabled) would otherwise keep serving stale code for
    // a while after an edit, so a re-run right after changing app code could test the old version
    $args = ['-d', 'variables_order=EGPCS', '-d', 'opcache.revalidate_freq=0', '-S', $host, '-t', $docRoot];
    $env  = array_merge(getenv(), [
      // Test-only routes (test-login, the fake OAuth provider) and the test database, regardless of .env —
      // so a local dev site can keep TEST_MODE off in .env and talk to the real DeviantArt/Discord
      'TEST_MODE'              => 'true',
      // Absolute URLs the app builds (e.g. OAuth redirect URIs) must point back at this server
      'APP_URL'                => TestSeederConstants::BASE_URL,
      // The fake OAuth provider (TestOAuthController) is served by this same server and called
      // server-side mid-request, which would deadlock a single-worker server
      'PHP_CLI_SERVER_WORKERS' => '4',
    ]);

    $pid = pcntl_fork();

    if ($pid === -1) {
      self::startViaProc($args, $env);
      return;
    }

    if ($pid === 0) {
      // Child: become the PHP built-in server
      pcntl_exec(PHP_BINARY, $args, $env);
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

  private static function startViaProc(array $args, array $env): void {
    // Store handle as a static property — if it goes out of scope PHP kills the child
    self::$procHandle = proc_open([PHP_BINARY, ...$args], [], $pipes, null, $env);
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
    self::removeSeededFiles();
  }

  /**
   * The seeder writes cutie mark files for its fixed IDs (900000+, deliberately outside the real range because
   * fs/ is shared with the dev site). They have no row in any other database, so they would show up as orphans
   * when rehearsing a file migration against a prod copy; remove them when the run ends.
   */
  private static function removeSeededFiles(): void {
    $fs = dirname(__DIR__, 3).'/fs/';
    foreach (['cm_source', 'cm_tokenized', 'cg_render/cutiemark'] as $folder) {
      foreach (glob($fs.$folder.'/9?????.svg') ?: [] as $file) {
        if ((int)basename($file, '.svg') >= 900000)
          @unlink($file);
      }
    }
  }
}
