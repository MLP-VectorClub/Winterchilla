<?php

use Tests\Browser\Helpers\TestSeederConstants;

/*
 * Drives the real /discord-connect account-linking flow against the TEST_MODE fake provider
 * (TestOAuthController), whose consent page picks the Discord identity and server membership.
 *
 * Order matters: tests share one database per file. The admin is used for the not-linked cases first,
 * then both seeded users end up linked.
 */

$base = TestSeederConstants::BASE_URL;

function beginDiscordConnect(string $base, int $user_id) {
  return visit($base . '/test-login/' . $user_id)
    ->navigate($base . '/discord-connect/begin')
    ->assertSee('Fake discord')
    ->assertSeeIn('[data-testid="oauth-scope"]', 'identify guilds');
}

it('refuses to start linking for guests', function () use ($base) {
  visit($base . '/discord-connect/begin')
    ->assertSee('403')
    ->assertDontSee('Fake discord');
});

it('leaves the account unlinked when the user denies access', function () use ($base) {
  beginDiscordConnect($base, TestSeederConstants::ADMIN_ID)
    ->click('[data-testid="oauth-deny"]')
    ->assertPathIs('/users/' . TestSeederConstants::ADMIN_ID . '/account')
    ->assertSeeIn('#discord-connect', 'Link your account');
});

it('ignores a callback whose state does not match the session', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/discord-connect/end?code=some-code&state=not-the-session-state')
    ->assertPathIs('/users/' . TestSeederConstants::ADMIN_ID . '/account')
    ->assertSeeIn('#discord-connect', 'Link your account');
});

it('links a Discord account that has not joined the server', function () use ($base) {
  beginDiscordConnect($base, TestSeederConstants::ADMIN_ID)
    ->fill('[data-testid="oauth-discord-id"]', '100000000000000002')
    ->fill('[data-testid="oauth-discord-username"]', 'admin_on_discord')
    ->click('[data-testid="oauth-approve"]')
    ->assertPathIs('/users/' . TestSeederConstants::ADMIN_ID . '/account')
    ->assertNoJavaScriptErrors()
    ->assertSeeIn('#discord-connect', 'is linked to')
    ->assertSeeIn('#discord-connect', 'admin_on_discord')
    ->assertSeeIn('#discord-connect', "haven't joined our")
    ->assertPresent('#discord-connect button.unlink');
});

it('links a Discord account that is a server member', function () use ($base) {
  beginDiscordConnect($base, TestSeederConstants::USER_ID)
    ->fill('[data-testid="oauth-discord-id"]', '100000000000000001')
    ->fill('[data-testid="oauth-discord-username"]', 'user_on_discord')
    ->check('[data-testid="oauth-discord-in-guild"]')
    ->click('[data-testid="oauth-approve"]')
    ->assertPathIs('/users/' . TestSeederConstants::USER_ID . '/account')
    ->assertSeeIn('#discord-connect', 'is linked to')
    ->assertSeeIn('#discord-connect', 'user_on_discord')
    ->assertSeeIn('#discord-connect', "joined our Discord server");
});

it('skips straight back to the account page when already linked', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::USER_ID)
    ->navigate($base . '/discord-connect/begin')
    ->assertPathIs('/users/' . TestSeederConstants::USER_ID . '/account')
    ->assertDontSee('Fake discord')
    ->assertSeeIn('#discord-connect', 'is linked to');
});
