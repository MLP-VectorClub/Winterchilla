<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /da-auth/status and /da-auth/sign-out (tag: authentication).
// Bodies use camelCase keys, no `status` envelope; errors are {message} with a real HTTP status.

it('GET /da-auth/status reports a guest as deleted', function () {
  $r = ApiClient::guest()->get('/da-auth/status');

  expect($r['status'])->toBe(200)
    ->and($r['contentType'])->toStartWith('application/json')
    ->and($r['json'])->toHaveKeys(['deleted', 'loggedIn'])
    ->and($r['json']['deleted'])->toBeTrue()
    ->and($r['json'])->not->toHaveKey('status');
});

it('GET /da-auth/status reports a signed-in user as not deleted', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/da-auth/status');

  expect($r['status'])->toBe(200)
    ->and($r['json']['deleted'])->toBeFalse()
    ->and($r['json']['loggedIn'])->toBeString();
});

it('rejects unsupported methods on /da-auth/status with 405', function () {
  $r = ApiClient::guest()->post('/da-auth/status');

  expect($r['status'])->toBe(405)
    ->and($r['json'])->toHaveKey('message')
    ->and($r['json'])->not->toHaveKey('status');
});

it('POST /da-auth/sign-out is a 204 no-op for guests', function () {
  $r = ApiClient::guest()->post('/da-auth/sign-out');

  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
});

it('POST /da-auth/sign-out signs the user out with 204', function () {
  $client = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  expect($client->get('/da-auth/status')['json']['deleted'])->toBeFalse();

  $r = $client->post('/da-auth/sign-out');
  expect($r['status'])->toBe(204);

  expect($client->get('/da-auth/status')['json']['deleted'])->toBeTrue();
});

it('forbids non-staff from signing out another user everywhere with 403', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)
    ->post('/da-auth/sign-out', ['everywhere' => 1, 'user_id' => TestSeederConstants::ADMIN_ID]);

  expect($r['status'])->toBe(403)
    ->and($r['json'])->toHaveKey('message')
    ->and($r['json'])->not->toHaveKey('status');
});

it('404s when staff target a user that does not exist', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)
    ->post('/da-auth/sign-out', ['everywhere' => 1, 'user_id' => 987654]);

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message');
});

it('404s unknown API endpoints with an error body', function () {
  $r = ApiClient::guest()->get('/does-not-exist');

  expect($r['status'])->toBe(404)
    ->and($r['json'])->toHaveKey('message')
    ->and($r['json'])->not->toHaveKey('status');
});

it('rejects state-changing requests without a CSRF token with 419', function () {
  $ch = curl_init(TestSeederConstants::BASE_URL . TestSeederConstants::API_PATH . '/da-auth/sign-out');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
  $body = curl_exec($ch);

  expect(curl_getinfo($ch, CURLINFO_HTTP_CODE))->toBe(419)
    ->and(json_decode((string)$body, true))->toHaveKey('message');
});
