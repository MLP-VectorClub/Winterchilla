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
    ->and($r['json'])->not->toHaveKey('status');
});

it('404s when marking a notification that does not exist', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->post('/notif/987654/mark-read');

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message');
});
