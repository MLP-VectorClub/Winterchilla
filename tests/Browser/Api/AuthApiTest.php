<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /users/session/status and /users/signout (tag: authentication).
// Bodies use camelCase keys, no `status` envelope; errors are {message} with a real HTTP status.

it('GET /users/session/status reports a guest as deleted', function () {
  $r = ApiClient::guest()->get('/users/session/status');

  expect($r['status'])->toBe(200)
    ->and($r['contentType'])->toStartWith('application/json')
    ->and($r['json'])->toHaveKeys(['deleted', 'loggedIn'])
    ->and($r['json']['deleted'])->toBeTrue()
    ->and($r['json'])->not->toHaveKey('status');
});

it('GET /users/session/status reports a signed-in user as not deleted', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/users/session/status');

  expect($r['status'])->toBe(200)
    ->and($r['json']['deleted'])->toBeFalse()
    ->and($r['json']['loggedIn'])->toBeString();
});

it('rejects unsupported methods on /users/session/status with 405', function () {
  $r = ApiClient::guest()->post('/users/session/status');

  expect($r['status'])->toBe(405)
    ->and($r['json'])->toHaveKey('message')
    ->and($r['json'])->not->toHaveKey('status');
});

it('POST /users/signout is a 204 no-op for guests', function () {
  $r = ApiClient::guest()->post('/users/signout');

  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
});

it('POST /users/signout signs the user out with 204', function () {
  $client = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  expect($client->get('/users/session/status')['json']['deleted'])->toBeFalse();

  $r = $client->post('/users/signout');
  expect($r['status'])->toBe(204);

  expect($client->get('/users/session/status')['json']['deleted'])->toBeTrue();
});

it('forbids non-staff from signing out another user everywhere with 403', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)
    ->post('/users/signout', ['everywhere' => 1, 'user_id' => TestSeederConstants::ADMIN_ID]);

  expect($r['status'])->toBe(403)
    ->and($r['json'])->toHaveKey('message')
    ->and($r['json'])->not->toHaveKey('status');
});

it('404s when staff target a user that does not exist', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)
    ->post('/users/signout', ['everywhere' => 1, 'user_id' => 987654]);

  expect($r['status'])->toBe(404)->and($r['json'])->toHaveKey('message');
});

it('404s unknown API endpoints with an error body', function () {
  $r = ApiClient::guest()->get('/does-not-exist');

  expect($r['status'])->toBe(404)
    ->and($r['json'])->toHaveKey('message')
    ->and($r['json'])->not->toHaveKey('status');
});

it('rejects state-changing requests without a CSRF token with 419', function () {
  $ch = curl_init(TestSeederConstants::BASE_URL . TestSeederConstants::API_PATH . '/users/signout');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
  $body = curl_exec($ch);

  expect(curl_getinfo($ch, CURLINFO_HTTP_CODE))->toBe(419)
    ->and(json_decode((string)$body, true))->toHaveKey('message');
});

it('answers a JSON request for an HTML page with 406 instead of dumping the page data', function () {
  // /cg/pony passes Eloquent-style models with circular references to the view; logging them used to overflow the headers
  $ch = curl_init(TestSeederConstants::BASE_URL . '/cg/pony');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
  $body = curl_exec($ch);

  expect(curl_getinfo($ch, CURLINFO_HTTP_CODE))->toBe(406)
    ->and(json_decode((string)$body, true))->toHaveKey('message');
});
