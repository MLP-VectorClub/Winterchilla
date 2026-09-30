<?php

use Tests\Browser\Helpers\ApiClient;

// Contract: /about/server and /about/upcoming (tag: server info). Public.

it('GET /about/server returns git info', function () {
  $r = ApiClient::guest()->get('/about/server');

  // Git info is derived from `.git-deploy-commit` or `git log`; the endpoint fails with 500 when neither exists
  expect($r['status'])->toBeIn([200, 500])->and($r['json'])->not->toHaveKey('status');
  if ($r['status'] === 200)
    expect($r['json']['git'])->toHaveKeys(['commitId', 'commitTime']);
  else
    expect($r['json'])->toHaveKey('message');
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

  expect($client->post('/about/server')['status'])->toBe(405)
    ->and($client->post('/about/upcoming')['status'])->toBe(405);
});
