<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /notif and /notif/{id}/mark-read (tag: notifications). Requires authentication.

it('rejects guests with 401', function () {
  $guest = ApiClient::guest();

  expect($guest->get('/notif')['status'])->toBe(401)
    ->and($guest->post('/notif/1/mark-read')['status'])->toBe(401);
});

it('lists the current user\'s unread notifications as HTML', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/notif');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKey('list')
    ->and($r['json'])->not->toHaveKey('status')
    ->and($r['json']['list'])->toContain('data-id=\'' . TestSeederConstants::NOTIFICATION_ID . '\'');
});

it('does not list other users\' notifications', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/notif');

  expect($r['json']['list'])->not->toContain('data-id=\'' . TestSeederConstants::ADMIN_NOTIFICATION_ID . '\'');
});

it('marks a notification as read with 204 and stops listing it', function () {
  $client = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $id = TestSeederConstants::NOTIFICATION_MARK_READ_ID;
  expect($client->get('/notif')['json']['list'])->toContain('data-id=\'' . $id . '\'');

  $r = $client->post('/notif/' . $id . '/mark-read');
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');

  expect($client->get('/notif')['json']['list'])->not->toContain('data-id=\'' . $id . '\'');
});

it('404s when marking another user\'s notification as read', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)
    ->post('/notif/' . TestSeederConstants::ADMIN_NOTIFICATION_ID . '/mark-read');

  expect($r['status'])->toBe(404);
});

it('404s when marking a notification that does not exist', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->post('/notif/987654/mark-read');

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message');
});
