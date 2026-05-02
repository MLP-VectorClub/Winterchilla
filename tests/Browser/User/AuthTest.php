<?php

use Tests\Browser\Helpers\TestSeederConstants;

use function Pest\Browser\page;

$base = TestSeederConstants::BASE_URL;

it('creates a session via test-login and shows signed-in state', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::USER_ID);
  page()->waitForURL('**/');
  // Sign in button should no longer be present
  $signIn = page()->locator('a[href*="da-auth"], .signin, #signin');
  expect($signIn->count())->toBe(0);
});

it('can sign out after logging in', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::USER_ID);
  page()->waitForURL('**/');

  // Click sign out — try common selectors
  $signOut = page()->locator('a[href*="sign-out"], a[href*="signout"], .signout, #signout, a:has-text("Sign out"), a:has-text("Log out")');
  $signOut->first()->click();

  // After sign out, sign in button should appear again
  page()->waitForSelector('a[href*="da-auth"], .signin, #signin, a:has-text("Sign in"), a:has-text("Log in")');
});

it('shows a 403 or redirect for the admin panel as a guest', function () use ($base) {
  $response = page()->goto($base . '/admin');
  expect($response->status())->toBeIn([302, 401, 403]);
});

it('shows a 403 or redirect for admin logs as a guest', function () use ($base) {
  $response = page()->goto($base . '/admin/logs');
  expect($response->status())->toBeIn([302, 401, 403]);
});
