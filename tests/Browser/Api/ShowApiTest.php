<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /show endpoints (tags: shows, posts).

$showId = TestSeederConstants::SHOW_ID;
$airs = '2011-02-03 04:05';

function createMovie(ApiClient $admin, array $overrides = []):array {
  return $admin->post('/show', $overrides + ['type' => 'movie', 'title' => 'Contract Test Movie', 'airs' => '2011-02-03 04:05']);
}

it('GET /show/{id} returns the show with camelCase keys', function () use ($showId) {
  $r = ApiClient::guest()->get('/show/' . $showId);

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKey('show')->not->toHaveKey('status')
    ->and($r['json']['show'])->toHaveKeys(['id', 'type', 'season', 'episode', 'title', 'airs', 'postedBy'])
    ->and($r['json']['show']['id'])->toBe($showId)
    ->and($r['json']['show'])->not->toHaveKey('posted_by');
});

it('GET /show/{id} includes its state, permissions and related appearances', function () use ($showId) {
  $guest = ApiClient::guest()->get('/show/' . $showId)['json']['show'];
  $staff = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get('/show/' . $showId)['json']['show'];

  expect($guest)->toHaveKeys(['aired', 'willAir', 'canEdit', 'relatedAppearances'])
    ->and($guest['canEdit'])->toBeFalse()
    ->and($guest['relatedAppearances'])->toBeArray()
    ->and($staff['canEdit'])->toBeTrue();
});

it('GET /show/{id} 404s for a missing show', function () {
  $r = ApiClient::guest()->get('/show/987654');

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message');
});

it('requires staff to create, update or delete shows', function () use ($showId) {
  $guest = ApiClient::guest();
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  expect($guest->post('/show', ['type' => 'movie'])['status'])->toBe(401)
    ->and($user->post('/show', ['type' => 'movie'])['status'])->toBe(403)
    ->and($user->request('PUT', '/show/' . $showId, ['title' => 'x'])['status'])->toBe(403)
    ->and($user->request('DELETE', '/show/' . $showId)['status'])->toBe(403);
});

it('validates show input with 422 and field errors', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/show');
  expect($r['status'])->toBe(422)
    ->and($r['json'])->toHaveKeys(['message', 'errors'])
    ->and($r['json']['errors'])->toHaveKey('type');

  $r = createMovie($admin, ['title' => 'no']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('title');

  $r = createMovie($admin, ['airs' => '2001-01-01']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('airs');
});

it('creates (201), updates (204) and deletes a show entry', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = createMovie($admin);
  expect($r['status'])->toBe(201)
    ->and($r['json'])->toHaveKeys(['id', 'url'])
    ->and($r['json']['url'])->toContain('/movie/' . $r['json']['id']);
  $id = $r['json']['id'];

  $r = $admin->request('PUT', '/show/' . $id, ['type' => 'movie', 'title' => 'Renamed Contract Movie', 'airs' => '2012-03-04 05:06']);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($admin->get('/show/' . $id)['json']['show']['title'])->toBe('Renamed Contract Movie');

  $r = $admin->request('DELETE', '/show/' . $id);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('upcoming')->not->toHaveKey('status');
  expect($admin->get('/show/' . $id)['status'])->toBe(404);
});

it('409s when an update would duplicate another episode\'s season and episode number', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/show', ['type' => 'episode', 'season' => 1, 'episode' => 2, 'title' => 'Contract Episode Two', 'airs' => '2011-02-03 04:05']);
  expect($r['status'])->toBe(201);
  $id = $r['json']['id'];

  $r = $admin->request('PUT', '/show/' . $id, ['season' => 1, 'episode' => 1, 'title' => 'Contract Episode Two', 'airs' => '2011-02-03 04:05']);
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKey('message');

  $admin->request('DELETE', '/show/' . $id);
});

it('GET /show/{id}/posts returns a rendered section and validates the section', function () use ($showId) {
  $guest = ApiClient::guest();

  $r = $guest->get('/show/' . $showId . '/posts', ['section' => 'requests']);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('render')->not->toHaveKey('status');

  $r = $guest->get('/show/' . $showId . '/posts', ['section' => 'nope']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('section');

  expect($guest->get('/show/987654/posts', ['section' => 'requests'])['status'])->toBe(404);
});

it('GET /show/{id}/vote returns the vote counts', function () use ($showId) {
  $r = ApiClient::guest()->get('/show/' . $showId . '/vote');

  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('data')->not->toHaveKey('status');

  $r = ApiClient::guest()->get('/show/' . $showId . '/vote', ['html' => 1]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('html');
});

it('lets a signed-in user vote once on an aired episode', function () use ($showId) {
  expect(ApiClient::guest()->post('/show/' . $showId . '/vote', ['vote' => 5])['status'])->toBe(401);

  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  $r = $user->post('/show/' . $showId . '/vote', ['vote' => 9]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('vote');

  $r = $user->post('/show/' . $showId . '/vote', ['vote' => 5]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('newhtml');

  $r = $user->post('/show/' . $showId . '/vote', ['vote' => 4]);
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKey('message');
});

it('409s when voting on a show that has not aired yet', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $r = createMovie($admin, ['airs' => date('Y-m-d H:i', strtotime('+1 year'))]);
  expect($r['status'])->toBe(201);
  $id = $r['json']['id'];

  expect($admin->post('/show/' . $id . '/vote', ['vote' => 5])['status'])->toBe(409);

  $admin->request('DELETE', '/show/' . $id);
});

it('manages appearance relations for staff only', function () use ($showId) {
  expect(ApiClient::guest()->get('/show/' . $showId . '/appearances')['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/show/' . $showId . '/appearances')['status'])->toBe(403);

  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $r = $admin->get('/show/' . $showId . '/appearances');
  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['groups', 'entries', 'linkedIds'])
    ->and($r['json']['linkedIds'])->toBe([]);

  $r = $admin->request('PUT', '/show/' . $showId . '/appearances', ['ids' => (string)TestSeederConstants::APPEARANCE_ID]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('section');
  expect($admin->get('/show/' . $showId . '/appearances')['json']['linkedIds'])->toBe([TestSeederConstants::APPEARANCE_ID]);

  $r = $admin->request('PUT', '/show/' . $showId . '/appearances', ['ids' => '']);
  expect($r['status'])->toBe(200);
  expect($admin->get('/show/' . $showId . '/appearances')['json']['linkedIds'])->toBe([]);

  expect($admin->get('/show/987654/appearances')['status'])->toBe(404);
});

it('GET /show/next reports a hiatus with 404 when nothing is upcoming', function () {
  $r = ApiClient::guest()->get('/show/next');

  expect($r['status'])->toBe(404)
    ->and($r['json'])->toHaveKeys(['message', 'hiatus'])
    ->and($r['json']['hiatus'])->toBeTrue();
});

it('GET /show/prefill suggests the next episode for staff', function () {
  expect(ApiClient::guest()->get('/show/prefill')['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/show/prefill')['status'])->toBe(403);

  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get('/show/prefill');
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['season', 'episode', 'no', 'airday']);
});
