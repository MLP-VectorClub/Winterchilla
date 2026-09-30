<?php

use Tests\Browser\Helpers\ApiClient;

// Contract: /about/connection, /about/members and /about/upcoming (tag: server info). Public.

it('GET /about/connection returns build and connection info', function () {
  $r = ApiClient::guest()->get('/about/connection');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['commitId', 'commitTime', 'ip', 'proxiedIps', 'userAgent'])
    ->and($r['json']['ip'])->toBeString()
    ->and($r['json'])->not->toHaveKey('status');
});

it('GET /about/members lists the members that are not regular users', function () {
  $r = ApiClient::guest()->get('/about/members');

  expect($r['status'])->toBe(200)->and($r['json'])->toBeArray();
  $roles = array_unique(array_column($r['json'], 'role'));
  expect($roles)->not->toContain('user')->and(array_column($r['json'], 'name'))->toContain('TestAdmin');
  expect($r['json'][0])->toHaveKeys(['id', 'name', 'role', 'avatarUrl']);
});

it('GET /about/upcoming returns rendered HTML', function () {
  $r = ApiClient::guest()->get('/about/upcoming');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKey('html')
    ->and($r['json']['html'])->toBeString()
    ->and($r['json'])->not->toHaveKey('status');
});

it('rejects non-GET methods with 405', function () {
  $client = ApiClient::guest();

  expect($client->post('/about/connection')['status'])->toBe(405)
    ->and($client->post('/about/upcoming')['status'])->toBe(405);
});
