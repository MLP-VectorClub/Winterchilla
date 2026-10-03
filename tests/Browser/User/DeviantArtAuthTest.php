<?php

// Winterchilla's own fake OAuth provider / dialog test page: not applicable to another implementation (see TestSeederConstants::external())
uses()->group('winterchilla-only');

use Tests\Browser\Helpers\TestSeederConstants;

/*
 * Drives the real /da-auth flow against the TEST_MODE fake provider (TestOAuthController), whose consent
 * page picks the DeviantArt identity to sign in as.
 *
 * Uses the full-page redirect mode (/da-auth/begin?return=...), which is what the site falls back to when
 * its sign-in popup can't be used; Pest can't drive popups, so the popup-to-opener hand-off
 * (login_confirm calling window.opener.__authCallback) isn't covered here.
 *
 * Order matters: tests share one database per file, and the lockout test must stay last since it leaves
 * this IP blocked from signing in.
 */

$base = TestSeederConstants::baseUrl();

function beginDeviantArtSignIn(string $base, string $return = '/cg') {
  return visit($base . '/da-auth/begin?return=' . rawurlencode($return))
    ->assertSee('Fake deviantart')
    ->assertSeeIn('[data-testid="oauth-scope"]', 'user');
}

it('signs in a new DeviantArt user and creates their account', function () use ($base) {
  beginDeviantArtSignIn($base)
    ->fill('[data-testid="oauth-da-username"]', 'OAuthNewUser')
    ->click('[data-testid="oauth-approve"]')
    ->assertPathIs('/cg')
    ->assertNoJavaScriptErrors()
    ->assertSeeIn('.logged-in .user-name', 'OAuthNewUser')
    ->navigate($base . '/users')
    ->assertSee('OAuthNewUser');
});

it('signs in an existing user by their DeviantArt ID', function () use ($base) {
  beginDeviantArtSignIn($base)
    ->fill('[data-testid="oauth-da-userid"]', TestSeederConstants::USER_DA_ID)
    ->fill('[data-testid="oauth-da-username"]', 'TestUser')
    ->click('[data-testid="oauth-approve"]')
    ->assertPathIs('/cg')
    ->assertSeeIn('.logged-in .user-name', 'TestUser')
    ->navigate($base . '/users/' . TestSeederConstants::USER_ID . '/account')
    ->assertSee('Account Settings');
});

it('returns to the page the sign-in started from', function () use ($base) {
  beginDeviantArtSignIn($base, '/about')
    ->click('[data-testid="oauth-approve"]')
    ->assertPathIs('/about')
    ->assertSeeIn('.logged-in .user-name', 'OAuthNewUser');
});

it('shows an error when the user denies access', function () use ($base) {
  beginDeviantArtSignIn($base)
    ->click('[data-testid="oauth-deny"]')
    ->assertSee('DeviantArt authentication error')
    ->assertSee('access_denied')
    ->assertSee('You decided not to allow the site to verify your identity')
    ->assertSee('Sign in');
});

it('rejects a callback whose state does not match the session', function () use ($base) {
  visit($base . '/da-auth/end?code=some-code&state=not-the-session-state')
    ->assertSee('DeviantArt authentication error')
    ->assertSee('unauthorized_client');
});

it('sends a callback without a code or state back home', function () use ($base) {
  visit($base . '/da-auth/end')
    ->assertPathIs('/cg')
    ->assertDontSee('DeviantArt authentication error');
});

it('shows an error when the code cannot be exchanged for a token', function () use ($base) {
  beginDeviantArtSignIn($base)
    ->click('[data-testid="oauth-approve-invalid-code"]')
    ->assertSee('DeviantArt authentication error')
    ->assertSee('invalid_grant')
    ->assertSee('Invalid or expired authorization code.');
});

it('renames the local user when their DeviantArt username changed', function () use ($base) {
  beginDeviantArtSignIn($base)
    ->fill('[data-testid="oauth-da-userid"]', TestSeederConstants::ADMIN_DA_ID)
    ->fill('[data-testid="oauth-da-username"]', 'RenamedAdmin')
    ->click('[data-testid="oauth-approve"]')
    ->assertPathIs('/cg')
    ->assertSeeIn('.logged-in .user-name', 'RenamedAdmin');
});

it('does not reuse the return URL of an abandoned sign-in', function () use ($base) {
  // A full-page sign-in saves its return URL in the session, then gets abandoned on the consent page...
  beginDeviantArtSignIn($base, '/about')
    // ...and a popup sign-in (which never passes a return URL) completes later in the same session
    ->navigate($base . '/da-auth/begin')
    ->assertSee('Fake deviantart')
    ->click('[data-testid="oauth-approve"]')
    // It must end on the page that reports back to the opener, not on the stale return URL
    ->assertPathIs('/da-auth/end')
    ->assertSee("You're signed in");
});

// Keep last: leaves this IP locked out of signing in for the rest of the file
it('locks sign-in out after repeated failed token exchanges', function () use ($base) {
  // One failure was already recorded by the invalid-code test above; five within a few minutes trip it
  for ($i = 0; $i < 4; $i++){
    beginDeviantArtSignIn($base)
      ->click('[data-testid="oauth-approve-invalid-code"]')
      ->assertSee('invalid_grant');
  }

  beginDeviantArtSignIn($base)
    ->click('[data-testid="oauth-approve"]')
    ->assertSee('DeviantArt authentication error')
    ->assertSee('time_out')
    ->assertSee("You've made too many failed login attempts");
});
