<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /event/... endpoints (tag: events). Managing events and receiving entries is disabled for now,
// which is reported as 501 after the permission checks.

$eventId = TestSeederConstants::EVENT_ID;

it('disables event management for staff with 501 and enforces permissions first', function () use ($eventId) {
  $guest = ApiClient::guest();
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  foreach (['GET', 'PUT', 'DELETE'] as $method) {
    expect($guest->request($method, '/event/' . $eventId)['status'])->toBe(401)
      ->and($user->request($method, '/event/' . $eventId)['status'])->toBe(403);
  }
  expect($guest->post('/event')['status'])->toBe(401)
    ->and($user->post('/event/' . $eventId . '/finalize')['status'])->toBe(403);

  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  foreach ([['GET', '/event/' . $eventId], ['POST', '/event'], ['PUT', '/event/' . $eventId], ['DELETE', '/event/' . $eventId], ['POST', '/event/' . $eventId . '/finalize']] as [$method, $path]) {
    $r = $admin->request($method, $path);
    expect($r['status'])->toBe(501)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');
  }
});

it('rejects entry submissions with 401 for guests and 501 for signed-in users', function () use ($eventId) {
  expect(ApiClient::guest()->post('/event/' . $eventId . '/check-entries')['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->post('/event/' . $eventId . '/check-entries')['status'])->toBe(501);
});

it('requires authentication to read or change an entry', function () {
  $guest = ApiClient::guest();
  $path = '/event/entry/' . TestSeederConstants::EVENT_ENTRY_ID;

  expect($guest->get($path)['status'])->toBe(401)
    ->and($guest->request('PUT', $path)['status'])->toBe(401)
    ->and($guest->request('DELETE', $path)['status'])->toBe(401);
});

it('returns an entry to its owner and staff with camelCase keys', function () {
  $path = '/event/entry/' . TestSeederConstants::EVENT_ENTRY_ID;

  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get($path);
  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['link', 'title', 'prevSrc'])->not->toHaveKey('status')
    ->and($r['json']['title'])->toBe('Seeded Entry');

  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get($path)['status'])->toBe(200);
});

it('forbids managing someone else\'s entry with 403 and 404s for missing entries', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $others = '/event/entry/' . TestSeederConstants::ADMIN_EVENT_ENTRY_ID;

  expect($user->get($others)['status'])->toBe(403)
    ->and($user->request('PUT', $others)['status'])->toBe(403)
    ->and($user->request('DELETE', $others)['status'])->toBe(403)
    ->and($user->get('/event/entry/987654')['status'])->toBe(404);
});

it('validates entry input with 422 and field errors', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $path = '/event/entry/' . TestSeederConstants::EVENT_ENTRY_ID;

  $r = $user->request('PUT', $path);
  expect($r['status'])->toBe(422)
    ->and($r['json'])->toHaveKeys(['message', 'errors'])
    ->and($r['json']['errors'])->toHaveKey('link');
});

it('deletes an entry with 204', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $path = '/event/entry/' . TestSeederConstants::EVENT_ENTRY_DELETE_ID;

  $r = $user->request('DELETE', $path);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($user->get($path)['status'])->toBe(404);
});

it('404s the lazyload endpoint for a missing entry', function () {
  $r = ApiClient::guest()->get('/event/entry/987654/lazyload');

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message');
});
