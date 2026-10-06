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
  foreach (['/da-auth/end?code=x&state=y', '/discord-connect/end?code=x&state=y'] as $path) {
    $r = $guest->pageResponse($path);
    expect($r['status'])->toBe(503, $path);
  }
})->skip($skip, 'Needs READ_ONLY=true')->group('read-only');
