<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /events/... endpoints (tag: events). Managing events and receiving entries is disabled for now,
// which is reported as 501 after the permission checks.

$eventId = TestSeederConstants::EVENT_ID;

it('disables event management for staff with 501 and enforces permissions first', function () use ($eventId) {
  $guest = ApiClient::guest();
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  foreach (['GET', 'PUT', 'DELETE'] as $method) {
    expect($guest->request($method, '/events/' . $eventId)['status'])->toBe(401)
      ->and($user->request($method, '/events/' . $eventId)['status'])->toBe(403);
  }
  expect($guest->post('/events')['status'])->toBe(401)
    ->and($user->post('/events/' . $eventId . '/finalize')['status'])->toBe(403);

  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  foreach ([['GET', '/events/' . $eventId], ['POST', '/events'], ['PUT', '/events/' . $eventId], ['DELETE', '/events/' . $eventId], ['POST', '/events/' . $eventId . '/finalize']] as [$method, $path]) {
    $r = $admin->request($method, $path);
    expect($r['status'])->toBe(501)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');
  }
});

it('rejects entry submissions with 401 for guests and 501 for signed-in users', function () use ($eventId) {
  expect(ApiClient::guest()->post('/events/' . $eventId . '/entries/check')['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->post('/events/' . $eventId . '/entries/check')['status'])->toBe(501);
});

it('requires authentication to read or change an entry', function () {
  $guest = ApiClient::guest();
  $path = '/event-entries/' . TestSeederConstants::EVENT_ENTRY_ID;

  expect($guest->get($path)['status'])->toBe(401)
    ->and($guest->request('PUT', $path)['status'])->toBe(401)
    ->and($guest->request('DELETE', $path)['status'])->toBe(401);
});

it('returns an entry to its owner and staff with camelCase keys', function () {
  $path = '/event-entries/' . TestSeederConstants::EVENT_ENTRY_ID;

  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get($path);
  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['link', 'title', 'prevSrc'])->not->toHaveKey('status')
    ->and($r['json']['title'])->toBe('Seeded Entry');

  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get($path)['status'])->toBe(200);
});

it('forbids managing someone else\'s entry with 403 and 404s for missing entries', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $others = '/event-entries/' . TestSeederConstants::ADMIN_EVENT_ENTRY_ID;

  expect($user->get($others)['status'])->toBe(403)
    ->and($user->request('PUT', $others)['status'])->toBe(403)
    ->and($user->request('DELETE', $others)['status'])->toBe(403)
    ->and($user->get('/event-entries/987654')['status'])->toBe(404);
});

it('validates entry input with 422 and field errors', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $path = '/event-entries/' . TestSeederConstants::EVENT_ENTRY_ID;

  $r = $user->request('PUT', $path);
  expect($r['status'])->toBe(422)
    ->and($r['json'])->toHaveKeys(['message', 'errors'])
    ->and($r['json']['errors'])->toHaveKey('link');
});

it('deletes an entry with 204', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $path = '/event-entries/' . TestSeederConstants::EVENT_ENTRY_DELETE_ID;

  $r = $user->request('DELETE', $path);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($user->get($path)['status'])->toBe(404);
});

it('404s the lazyload endpoint for a missing entry', function () {
  $r = ApiClient::guest()->get('/event-entries/987654/lazyload');

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message');
});

it('updates an entry from a cached deviation link and returns its rendered list item', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $path = '/event-entries/' . TestSeederConstants::EVENT_ENTRY_ID;

  $r = $user->request('PUT', $path, ['link' => 'http://fav.me/d1b2c3d', 'title' => 'Renamed Entry']);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('entryHtml')->and($r['json']['entryHtml'])->toContain('Renamed Entry');

  expect($user->get($path)['json']['title'])->toBe('Renamed Entry');
  $user->request('PUT', $path, ['link' => 'http://fav.me/d1b2c3d', 'title' => 'Seeded Entry']);

  // Links that aren't deviations or Sta.sh submissions are rejected
  $r = $user->request('PUT', $path, ['link' => 'http://example.com/whatever', 'title' => 'Seeded Entry']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('link');
});
