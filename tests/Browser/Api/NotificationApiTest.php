<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /notifications and /notifications/{id}/read (tag: notifications). Requires authentication.

it('rejects guests with 401', function () {
  $guest = ApiClient::guest();

  expect($guest->get('/notifications')['status'])->toBe(401)
    ->and($guest->post('/notifications/1/read')['status'])->toBe(401);
});

it('lists the current user\'s unread notifications as HTML', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/notifications');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKey('list')
    ->and($r['json'])->not->toHaveKey('status')
    ->and($r['json']['list'])->toContain('data-id=\'' . TestSeederConstants::NOTIFICATION_ID . '\'');
});

it('does not list other users\' notifications', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/notifications');

  expect($r['json']['list'])->not->toContain('data-id=\'' . TestSeederConstants::ADMIN_NOTIFICATION_ID . '\'');
});

it('marks a notification as read with 204 and stops listing it', function () {
  $client = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $id = TestSeederConstants::NOTIFICATION_MARK_READ_ID;
  expect($client->get('/notifications')['json']['list'])->toContain('data-id=\'' . $id . '\'');

  $r = $client->post('/notifications/' . $id . '/read');
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');

  expect($client->get('/notifications')['json']['list'])->not->toContain('data-id=\'' . $id . '\'');
});

it('404s when marking another user\'s notification as read', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)
    ->post('/notifications/' . TestSeederConstants::ADMIN_NOTIFICATION_ID . '/read');

  expect($r['status'])->toBe(404);
});

it('404s when marking a notification that does not exist', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->post('/notifications/987654/read');

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message');
});

it('keeps the legacy notification paths working as aliases', function () {
  $client = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  expect($client->get('/notif')['json']['list'])->toBe($client->get('/notifications')['json']['list'])
    ->and($client->post('/notif/987654/mark-read')['status'])->toBe(404)
    ->and(ApiClient::guest()->post('/notif/1/mark-read')['status'])->toBe(401);
});
