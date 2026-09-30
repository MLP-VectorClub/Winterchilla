<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: the public read API under /appearances (tags: color guide, appearances). This is the API Luna mirrors,
// so the error format follows Luna's: {message} with real statuses and validation errors as {message, errors}.

$appearanceId = TestSeederConstants::APPEARANCE_ID;

it('lists every appearance of a guide with camelCase keys', function () use ($appearanceId) {
  $r = ApiClient::guest()->get('/appearances/all', ['guide' => 'pony']);

  expect($r['status'])->toBe(200)
    ->and($r['contentType'])->toStartWith('application/json')
    ->and($r['json'])->toHaveKey('appearances')->not->toHaveKeys(['status', 'cachedOn', 'cachedFor']);
  $ids = array_column($r['json']['appearances'], 'id');
  expect($ids)->toContain($appearanceId);
  $first = $r['json']['appearances'][0];
  expect($first)->toHaveKeys(['id', 'label', 'createdAt', 'sprite', 'hasCutieMarks'])->not->toHaveKey('created_at');

  // Served from the cache the second time, byte for byte
  expect(ApiClient::guest()->get('/appearances/all', ['guide' => 'pony'])['body'])->toBe($r['body']);
});

it('validates the guide of /appearances/all with 422', function () {
  foreach ([[], ['guide' => 'nonsense']] as $query) {
    $r = ApiClient::guest()->get('/appearances/all', $query);
    expect($r['status'])->toBe(422)
      ->and($r['json'])->toHaveKeys(['message', 'errors'])
      ->and($r['json']['errors'])->toHaveKey('guide');
  }
});

it('searches a guide, or reports that ElasticSearch is unreachable', function () {
  $r = ApiClient::guest()->get('/appearances', ['guide' => 'pony']);

  expect($r['status'])->toBeIn([200, 503])->and($r['json'])->not->toHaveKey('status');
  if ($r['status'] === 200)
    expect($r['json'])->toHaveKeys(['appearances', 'pagination'])
      ->and($r['json']['pagination'])->toHaveKeys(['currentPage', 'totalPages', 'totalItems', 'itemsPerPage']);
  else
    expect($r['json'])->toHaveKey('message');
});

it('returns the color groups of an appearance', function () {
  $r = ApiClient::guest()->get('/appearances/' . TestSeederConstants::PERSONAL_APPEARANCE_ID . '/color-groups');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKey('colorGroups')->not->toHaveKey('status')
    ->and($r['json']['colorGroups'][0]['label'])->toBe('Personal Coat');
});

it('404s for a missing appearance', function () {
  $r = ApiClient::guest()->get('/appearances/987654/color-groups');

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');
});

it('hides private appearances from everyone but their owner and staff', function () {
  $path = '/appearances/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID . '/color-groups';

  $r = ApiClient::guest()->get($path);
  expect($r['status'])->toBe(403)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');

  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get($path)['status'])->toBe(200);
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get($path)['status'])->toBe(200);
});

it('returns a detailed appearance with color groups and cutie marks', function () use ($appearanceId) {
  $r = ApiClient::guest()->get("/appearances/$appearanceId");

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['id', 'label', 'createdAt', 'notes', 'tags', 'sprite', 'hasCutieMarks', 'colorGroups', 'cutieMarks'])
    ->and($r['json']['id'])->toBe($appearanceId)
    ->and($r['json'])->not->toHaveKeys(['status', 'created_at', 'color_groups']);
  $marks = array_column($r['json']['cutieMarks'], null, 'id');
  expect($marks)->toHaveKey(TestSeederConstants::CUTIEMARK_ID)
    ->and($marks[TestSeederConstants::CUTIEMARK_ID])->toHaveKeys(['id', 'viewUrl', 'facing', 'rotation'])
    ->and($marks[TestSeederConstants::CUTIEMARK_ID]['viewUrl'])->toStartWith('/cg/cutiemark/');
});

it('answers 404 for a missing appearance and 403 for a private one', function () {
  $guest = ApiClient::guest();

  expect($guest->get('/appearances/987654')['status'])->toBe(404);
  $r = $guest->get('/appearances/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID);
  expect($r['status'])->toBe(403)->and($r['json'])->toHaveKey('message');
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get('/appearances/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID)['status'])->toBe(200);
});

it('locates an appearance with preview colors', function () use ($appearanceId) {
  $r = ApiClient::guest()->get("/appearances/$appearanceId/locate");

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['id', 'label', 'guide', 'previewData'])
    ->and($r['json']['guide'])->toBe('pony')
    ->and($r['json']['previewData'])->toBeArray();
  expect(ApiClient::guest()->get('/appearances/987654/locate')['status'])->toBe(404)
    ->and(ApiClient::guest()->get('/appearances/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID . '/locate')['status'])->toBe(403);
});

it('lists pinned appearances of a guide', function () {
  $guest = ApiClient::guest();
  $r = $guest->get('/appearances/pinned', ['guide' => 'pony']);

  expect($r['status'])->toBe(200)->and($r['json'])->toBeArray();
  foreach ($r['json'] as $appearance)
    expect($appearance)->toHaveKeys(['id', 'label', 'colorGroups']);
  expect($guest->get('/appearances/pinned')['status'])->toBe(422)
    ->and($guest->get('/appearances/pinned', ['guide' => 'nope'])['json']['errors'])->toHaveKey('guide');
});

// The success body is a redirect to the generated file under /img/appearance_previews, which the PHP test server can't
// serve from the fs/ directory, so only the error paths are pinned here.
it('answers 404 and 403 for the preview of a missing or private appearance', function () {
  expect(ApiClient::guest()->get('/appearances/987654/preview')['status'])->toBe(404)
    ->and(ApiClient::guest()->get('/appearances/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID . '/preview')['status'])->toBe(403);
});

it('tells the current user whether they can edit the appearance', function () {
  $official = TestSeederConstants::APPEARANCE_ID;
  $personal = TestSeederConstants::PERSONAL_APPEARANCE_ID;

  expect(ApiClient::guest()->get("/appearances/$official")['json']['canEdit'])->toBeFalse();
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get("/appearances/$official")['json']['canEdit'])->toBeFalse();
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get("/appearances/$official")['json']['canEdit'])->toBeTrue();
  // A personal guide appearance can be edited by its owner
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get("/appearances/$personal")['json']['canEdit'])->toBeTrue();
  expect(ApiClient::guest()->get("/appearances/$personal")['json']['canEdit'])->toBeFalse();
});
