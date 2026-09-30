<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: the public user reads Luna mirrors: GET /users/{id}, /users/da/{username} and the staff-only GET /users.

it('returns the public information of a user by ID', function () {
  $r = ApiClient::guest()->get('/users/' . TestSeederConstants::USER_ID);

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['id', 'name', 'role', 'avatarUrl', 'avatarProvider'])
    ->and($r['json']['id'])->toBe(TestSeederConstants::USER_ID)
    ->and($r['json']['name'])->toBe('TestUser')
    ->and($r['json'])->not->toHaveKeys(['status', 'email']);
});

it('returns the same user by DeviantArt username', function () {
  $guest = ApiClient::guest();
  $r = $guest->get('/users/da/TestUser');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toBe($guest->get('/users/' . TestSeederConstants::USER_ID)['json']);
});

it('answers 404 for unknown users without asking DeviantArt', function () {
  $guest = ApiClient::guest();

  expect($guest->get('/users/987654')['status'])->toBe(404)
    ->and($guest->get('/users/da/NoSuchUserExistsHere')['status'])->toBe(404)
    ->and($guest->get('/users/987654')['json'])->toHaveKey('message');
});

it('lists regular users for staff only', function () {
  expect(ApiClient::guest()->get('/users')['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/users')['status'])->toBe(403);

  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get('/users');
  expect($r['status'])->toBe(200);
  $ids = array_column($r['json'], 'id');
  expect($ids)->toContain(TestSeederConstants::USER_ID)->not->toContain(TestSeederConstants::ADMIN_ID)
    ->and($r['json'][0])->toHaveKeys(['id', 'name', 'role'])
    ->and(array_unique(array_column($r['json'], 'role')))->toBe(['user']);
});
