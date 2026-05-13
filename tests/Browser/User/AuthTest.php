<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('creates a session via test-login and shows signed-in state', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::USER_ID)
    ->navigate($base . '/cg/pony')
    ->assertDontSee('Sign in');
});

it('can sign out after logging in', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::USER_ID)
    ->navigate($base . '/cg/pony')
    ->click('[data-testid="auth-signout"]')
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertSee('Sign in');
});

it('shows 403 to guests on the admin panel', function () use ($base) {
  visit($base . '/admin')
    ->assertSee('403');
});

it('shows 403 to guests on admin logs', function () use ($base) {
  visit($base . '/admin/logs')
    ->assertSee('403');
});
