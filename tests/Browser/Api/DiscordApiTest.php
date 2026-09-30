<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: POST /users/{id}/discord/sync and DELETE /users/{id}/discord (tag: discord). The user themselves or staff.
// A successful sync needs the real Discord API (it refreshes the token), so only the checks around it are pinned here.

it('requires authentication and ownership', function () {
  $id = TestSeederConstants::DISCORD_SYNCED_USER_ID;
  $guest = ApiClient::guest();
  $other = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  expect($guest->post("/users/$id/discord/sync")['status'])->toBe(401)
    ->and($guest->request('DELETE', "/users/$id/discord")['status'])->toBe(401)
    ->and($other->post("/users/$id/discord/sync")['status'])->toBe(403)
    ->and($other->request('DELETE', "/users/$id/discord")['status'])->toBe(403);
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->post('/users/987654/discord/sync')['status'])->toBe(404);
});

it('answers 409 when no Discord account is bound or linked', function () {
  $unbound = ApiClient::loggedInAs(TestSeederConstants::FRESH_USER_ID);
  $r = $unbound->post('/users/' . TestSeederConstants::FRESH_USER_ID . '/discord/sync');
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');
  expect($unbound->request('DELETE', '/users/' . TestSeederConstants::FRESH_USER_ID . '/discord')['status'])->toBe(409);

  // Known to the site, but the account was never linked (no access token)
  $id = TestSeederConstants::DISCORD_UNLINKED_USER_ID;
  expect(ApiClient::loggedInAs($id)->post("/users/$id/discord/sync")['status'])->toBe(409);
});

it('refuses to sync again within the cooldown with 429', function () {
  $id = TestSeederConstants::DISCORD_SYNCED_USER_ID;
  $r = ApiClient::loggedInAs($id)->post("/users/$id/discord/sync");

  expect($r['status'])->toBe(429)->and($r['json']['message'])->toContain('5 minutes');
  // Staff are subject to the cooldown as well
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->post("/users/$id/discord/sync")['status'])->toBe(429);
});

it('only answers the documented methods', function () {
  $id = TestSeederConstants::DISCORD_SYNCED_USER_ID;
  $client = ApiClient::loggedInAs($id);

  expect($client->request('DELETE', "/users/$id/discord/sync")['status'])->toBe(405)
    ->and($client->get("/users/$id/discord")['status'])->toBe(405);
});

it('unlinks a Discord account and forgets it', function () {
  $id = TestSeederConstants::DISCORD_LINKED_USER_ID;
  $client = ApiClient::loggedInAs($id);

  $r = $client->request('DELETE', "/users/$id/discord");
  expect($r['status'])->toBe(200)->and($r['json']['message'])->toContain('unlinked');
  // The member record is gone, so there is nothing left to unlink or sync
  expect($client->request('DELETE', "/users/$id/discord")['status'])->toBe(409)
    ->and($client->post("/users/$id/discord/sync")['status'])->toBe(409);
});
