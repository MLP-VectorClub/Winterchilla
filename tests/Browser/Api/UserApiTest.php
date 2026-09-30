<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /users/me and the /user/... endpoints (tags: authentication, users).
// Password and e-mail changes and e-mail verification are staff-only while under testing, hence the 403s for
// regular users. The password test signs the admin out everywhere and sets a password, so it goes last.

$userId = TestSeederConstants::USER_ID;
$adminId = TestSeederConstants::ADMIN_ID;

it('GET /users/me returns the signed-in user in camelCase', function () use ($userId) {
  $r = ApiClient::loggedInAs($userId)->get('/users/me');

  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['user', 'sessionUpdating'])->not->toHaveKey('status')
    ->and($r['json']['user'])->toHaveKeys(['id', 'name', 'role', 'avatarUrl', 'avatarProvider'])
    ->and($r['json']['user']['id'])->toBe($userId);
});

it('GET /users/me is 401 for guests', function () {
  $r = ApiClient::guest()->get('/users/me');

  expect($r['status'])->toBe(401)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');
});

it('GET /users/{id}/avatar-wrap returns rendered HTML and 404s for missing users', function () use ($userId) {
  $r = ApiClient::guest()->get('/users/' . $userId . '/avatar-wrap');
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('html');

  expect(ApiClient::guest()->get('/user/987654/avatar-wrap')['status'])->toBe(404);
});

it('DELETE /users/sessions/{id} needs a session, ownership and an existing session', function () use ($userId, $adminId) {
  expect(ApiClient::guest()->request('DELETE', '/users/sessions/1')['status'])->toBe(401);

  $user = ApiClient::loggedInAs($userId);
  expect($user->request('DELETE', '/users/sessions/987654')['status'])->toBe(404);

  // Find the admin's session ID in the rendered account page, then try to delete it as a regular user
  $admin = ApiClient::loggedInAs($adminId);
  preg_match('/id="session-(\d+)"/', $admin->page('/users/' . $adminId . '/account'), $m);
  expect($m)->not->toBeEmpty();
  $adminSession = $m[1];
  expect($user->request('DELETE', '/users/sessions/' . $adminSession)['status'])->toBe(403);
});

it('DELETE /users/sessions/{id} removes the user\'s own session with 204', function () use ($userId) {
  $user = ApiClient::loggedInAs($userId);
  preg_match('/id="session-(\d+)"/', $user->page('/users/' . $userId . '/account'), $m);
  expect($m)->not->toBeEmpty();

  $r = $user->request('DELETE', '/users/sessions/' . $m[1]);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($user->get('/users/me')['status'])->toBe(401);
});

it('PUT /users/{id}/role requires staff and validates the target and value', function () use ($userId, $adminId) {
  expect(ApiClient::guest()->request('PUT', '/users/' . $userId . '/role', ['value' => 'member'])['status'])->toBe(401);
  expect(ApiClient::loggedInAs($userId)->request('PUT', '/users/' . $userId . '/role', ['value' => 'member'])['status'])->toBe(403);

  $admin = ApiClient::loggedInAs($adminId);
  expect($admin->request('PUT', '/user/987654/role', ['value' => 'member'])['status'])->toBe(404);
  expect($admin->request('PUT', '/users/' . $adminId . '/role', ['value' => 'user'])['status'])->toBe(403);

  $r = $admin->request('PUT', '/users/' . $userId . '/role', ['value' => 'nonsense']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('value');
});

it('PUT /users/{id}/role changes a role with 204 and reports an unchanged role', function () use ($userId, $adminId) {
  $admin = ApiClient::loggedInAs($adminId);

  $r = $admin->request('PUT', '/users/' . $userId . '/role', ['value' => 'user']);
  expect($r['status'])->toBe(200)->and($r['json'])->toBe(['alreadyIn' => true]);

  expect($admin->request('PUT', '/users/' . $userId . '/role', ['value' => 'member'])['status'])->toBe(204);
  expect($admin->request('PUT', '/users/' . $userId . '/role', ['value' => 'user'])['status'])->toBe(204);
});

it('DELETE /users/{id}/contributions/cache requires staff and an existing user', function () use ($userId, $adminId) {
  expect(ApiClient::guest()->request('DELETE', '/users/' . $userId . '/contributions/cache')['status'])->toBe(401);
  expect(ApiClient::loggedInAs($userId)->request('DELETE', '/users/' . $userId . '/contributions/cache')['status'])->toBe(403);

  $admin = ApiClient::loggedInAs($adminId);
  expect($admin->request('DELETE', '/user/987654/contrib-cache')['status'])->toBe(404);

  $r = $admin->request('DELETE', '/users/' . $userId . '/contributions/cache');
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['message', 'html'])->not->toHaveKey('status');
});

it('POST /users/email/verify is staff-only and validates the hash', function () use ($userId, $adminId) {
  expect(ApiClient::loggedInAs($userId)->post('/users/email/verify', ['hash' => str_repeat('a', 128), 'action' => 'verify'])['status'])->toBe(403);

  $admin = ApiClient::loggedInAs($adminId);
  $r = $admin->post('/users/email/verify');
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('hash');

  $r = $admin->post('/users/email/verify', ['hash' => str_repeat('a', 128), 'action' => 'verify']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('hash');
});

it('POST /users/{id}/email-changes is staff-only and validates the new address', function () use ($userId, $adminId) {
  expect(ApiClient::guest()->post('/users/' . $userId . '/email-changes', ['newEmail' => 'a@example.com'])['status'])->toBe(401);
  expect(ApiClient::loggedInAs($userId)->post('/users/' . $userId . '/email-changes', ['newEmail' => 'a@example.com'])['status'])->toBe(403);

  $admin = ApiClient::loggedInAs($adminId);
  expect($admin->post('/user/987654/email', ['newEmail' => 'a@example.com'])['status'])->toBe(404);

  $r = $admin->post('/users/' . $adminId . '/email-changes');
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('newEmail');

  // The address is checked for deliverability (MX records) before anything else, so example.com is rejected.
  // The 409 for "set a password first" and the confirmation e-mail itself need a real domain, i.e. the network.
  $r = $admin->post('/users/' . $adminId . '/email-changes', ['newEmail' => 'admin@example.com']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('newEmail');
});

it('POST /users/me/password is staff-only and validates the new password', function () use ($userId, $adminId) {
  expect(ApiClient::guest()->post('/users/me/password', ['newPassword' => 'a-long-enough-password'])['status'])->toBe(401);
  expect(ApiClient::loggedInAs($userId)->post('/users/me/password', ['newPassword' => 'a-long-enough-password'])['status'])->toBe(403);

  $admin = ApiClient::loggedInAs($adminId);
  $r = $admin->post('/users/me/password', ['newPassword' => 'short']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('newPassword');

  $r = $admin->post('/users/me/password', ['newPassword' => 'a-long-enough-password']);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');

  // Setting a password signs the user out everywhere, and from now on the current password is required
  expect($admin->get('/users/me')['status'])->toBe(401);
  $admin = ApiClient::loggedInAs($adminId);
  $r = $admin->post('/users/me/password', ['newPassword' => 'another-long-password']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('currentPassword');
  $r = $admin->post('/users/me/password', ['newPassword' => 'another-long-password', 'currentPassword' => 'wrong-password']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('currentPassword');
});
