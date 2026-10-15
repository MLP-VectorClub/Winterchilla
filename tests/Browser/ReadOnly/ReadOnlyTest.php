<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

/**
 * Read-only mode (READ_ONLY=true, see scripts/ui-test-readonly.sh): the site shows what the database holds and nothing writes while pages are viewed,
 * even for signed in visitors (their session is not touched), and everything that would change data is refused. The server is started with a
 * read-only database session, so a write that slips through fails with SQLSTATE 25006 and shows up as a 5xx page here.
 */
$skip = fn() => getenv('READ_ONLY') !== 'true';

$pages = [
  '/cg', '/cg/pony', '/cg/pony/full', '/cg/pony/changes', '/cg/tags', '/cg/pony/v/' . TestSeederConstants::APPEARANCE_ID . '-Twilight-Sparkle',
  '/show', '/episode/' . TestSeederConstants::SHOW_ID, '/events', '/event/' . TestSeederConstants::EVENT_ID, '/users',
  '/users/' . TestSeederConstants::USER_ID, '/users/' . TestSeederConstants::USER_ID . '/account', '/users/' . TestSeederConstants::USER_ID . '/cg',
  '/users/' . TestSeederConstants::USER_ID . '/cg/point-history', '/admin', '/admin/logs', '/admin/usefullinks', '/admin/notices', '/admin/pcg-appearances',
  '/about', '/about/privacy', '/about/browser', '/muffin-rating',
];

it('renders every page for guests, users and admins without writing anything', function () use ($pages) {
  $failures = [];
  foreach ([null, TestSeederConstants::USER_ID, TestSeederConstants::ADMIN_ID] as $who) {
    $client = $who === null ? ApiClient::guest() : ApiClient::loggedInAs($who);
    foreach ($pages as $path) {
      $r = $client->pageResponse($path);
      $ok = $r['status'] < 500 && !preg_match('/Fatal error|Uncaught|SQLSTATE|read-only transaction/i', $r['body']);
      if (!$ok)
        $failures[] = ($who ?? 'guest') . " $path -> {$r['status']}";
    }
  }
  expect($failures)->toBe([]);
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');

it('shows the read-only notice', function () {
  expect(ApiClient::guest()->page('/cg'))->toContain('id=\'read-only-notice\'');
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->page('/admin'))->toContain('id=\'read-only-notice\'');
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');

it('points to the same page of the new site', function () {
  $page = ApiClient::guest()->page('/about?q=a');
  expect($page)->toContain("<a href='https://new.example/about?q=a'>new.example/about?q=a</a>");
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');

it('offers no way to sign in', function () {
  $page = ApiClient::guest()->page('/cg');
  expect($page)->not->toContain('id="signin"')->and($page)->not->toContain('da-login');
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');

it('reloads a post without checking its images or marking it broken', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get('/posts/' . TestSeederConstants::POST_ID . '/reload');
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('post');
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');

it('refuses every change with a 503 that says why', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  foreach ([['POST', '/notices'], ['PUT', '/notices/1'], ['DELETE', '/notices/1'], ['POST', '/posts'], ['PUT', '/appearances/1'], ['DELETE', '/appearances/2'], ['POST', '/tags']] as [$method, $path]) {
    $r = $admin->request($method, $path, ['label' => 'x']);
    expect($r['status'])->toBe(503, "$method $path")->and($r['json'])->toHaveKey('readOnly', true);
  }
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');

it('does not start a DeviantArt or Discord sign in that would write', function () {
  $guest = ApiClient::guest();
  foreach (['/da-auth/begin', '/da-auth/end?code=x&state=y', '/discord-connect/begin', '/discord-connect/end?code=x&state=y'] as $path) {
    $r = $guest->pageResponse($path);
    expect($r['status'])->toBe(503, $path);
  }
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');

/**
 * Whoever is signed in on the new site is shown as signed in here, by Luna's session cookie and the `luna_sessions` table (see App\LunaSession). The
 * cookie is the one tests/LunaSessionTest.php explains, for the session id below, which this test puts in the table (the test server reads the table
 * with the read-only role, which the table is created for here, as Luna's migrations would)
 */
it('shows a visitor signed in on the new site as signed in', function () {
  $lunaCookie = 'eyJpdiI6ImQ0NGVkU2lod1FMbTBONjZ1bUdMbEE9PSIsInZhbHVlIjoibldpcUNYZjQyY1JEQzVrQ3Bnci8yMTBPQ2dxRXVIN3hzejBmQllNTG1YM0h0eHROS0dNY2xyUXNlenhDWFB2YURScEpURTA5c2F2eVRwM01RdXgwaFFSL2ZaZGd2V1lpbmtPNHJKSnBONTlJM0VGMDlFYmdhNWJNOTVwcEtJQ0oiLCJtYWMiOiIzYjI5YzZiNTYxOGY5MzA0ODEwMThjYWFhNWQ3YzdlNGI0NzY3NDRkNjAwZWE5YjgzYzc5MTE4NjFlODJiMjQxIiwidGFnIjoiIn0=';
  $sessionId = 'Zk3dQmN8vTpL0aXrYb7cUeWfHgJiOsDq1n5MtVxC';
  // The database of the test server: .env says where it is, the environment may say it is another one
  $dotenv = (new \Symfony\Component\Dotenv\Dotenv())->parse((string)file_get_contents(dirname(__DIR__, 3) . '/.env'));
  $env = fn(string $name) => (getenv($name) ?: null) ?? ($dotenv[$name] ?? null);
  $pdo = new PDO(
    sprintf('pgsql:host=%s;dbname=%s', $env('DB_HOST') ?? 'localhost', $env('TEST_DB_NAME') ?? 'winterchilla_test'),
    $env('DB_USER'),
    $env('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
  $pdo->exec('CREATE TABLE IF NOT EXISTS luna_sessions (id varchar(255) PRIMARY KEY, user_id bigint NULL, ip_address varchar(45) NULL, user_agent text NULL, payload text NOT NULL, last_activity integer NOT NULL)');
  $pdo->prepare('DELETE FROM luna_sessions WHERE id = ?')->execute([$sessionId]);
  $insert = $pdo->prepare("INSERT INTO luna_sessions (id, user_id, payload, last_activity) VALUES (?, ?, '', ?)");

  try {
    $insert->execute([$sessionId, TestSeederConstants::USER_ID, time()]);
    $page = ApiClient::guest()->withCookie('test_session', $lunaCookie)->page('/cg');
    expect($page)->toContain('class="welcome"')->and($page)->not->toContain('class="login"')
      ->and($page)->not->toContain('id="signout"')->and($page)->not->toContain('id="signin"');

    // Another cookie, a session that has ended and a session nobody is signed in on are guests
    expect(ApiClient::guest()->withCookie('test_session', 'x' . $lunaCookie)->page('/cg'))->toContain('class="login"');
    $pdo->prepare('UPDATE luna_sessions SET last_activity = ? WHERE id = ?')->execute([time() - 43201 * 60, $sessionId]);
    expect(ApiClient::guest()->withCookie('test_session', $lunaCookie)->page('/cg'))->toContain('class="login"');
    $pdo->prepare('UPDATE luna_sessions SET last_activity = ?, user_id = NULL WHERE id = ?')->execute([time(), $sessionId]);
    expect(ApiClient::guest()->withCookie('test_session', $lunaCookie)->page('/cg'))->toContain('class="login"');
  } finally {
    $pdo->prepare('DELETE FROM luna_sessions WHERE id = ?')->execute([$sessionId]);
  }
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');

