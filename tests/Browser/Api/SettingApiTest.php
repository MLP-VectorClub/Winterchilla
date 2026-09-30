<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /settings/{key} (tag: settings). Requires staff.

it('rejects guests with 401', function () {
  $r = ApiClient::guest()->get('/settings/reservation_rules');

  expect($r['status'])->toBe(401)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');
});

it('forbids non-staff with 403', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/settings/reservation_rules');

  expect($r['status'])->toBe(403)->and($r['json'])->toHaveKey('message');
});

it('reads a setting as staff', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get('/settings/dev_role_label');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKey('value')
    ->and($r['json']['value'])->toBeString();
});

it('404s on an unknown setting key', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  expect($admin->get('/settings/nope')['status'])->toBe(404)
    ->and($admin->request('PUT', '/settings/nope', ['value' => 'x'])['status'])->toBe(404);
});

it('updates and resets a setting', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->request('PUT', '/settings/about_reservations', ['value' => 'Hello there']);
  expect($r['status'])->toBe(200)->and($r['json']['value'])->toContain('Hello there');
  expect($admin->get('/settings/about_reservations')['json']['value'])->toContain('Hello there');

  // Empty resets to the default, which is reported back as the effective value
  $r = $admin->request('PUT', '/settings/about_reservations', ['value' => '']);
  expect($r['status'])->toBe(200)->and($r['json']['value'])->toBe('');
});

it('returns a 422 validation error when the value is missing', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('PUT', '/settings/about_reservations');

  expect($r['status'])->toBe(422)
    ->and($r['json'])->toHaveKeys(['message', 'errors'])
    ->and($r['json']['errors'])->toHaveKey('value');
});

it('forbids staff who are not developers from changing dev_role_label with 403', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('PUT', '/settings/dev_role_label', ['value' => 'admin']);

  expect($r['status'])->toBe(403)->and($r['json'])->toHaveKey('message');
});
