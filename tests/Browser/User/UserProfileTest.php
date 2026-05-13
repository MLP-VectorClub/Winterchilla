<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('shows the profile page of the seeded regular user', function () use ($base) {
  visit($base . '/users/' . TestSeederConstants::USER_ID)
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('TestUser');
});

it('shows the profile page of the seeded admin user', function () use ($base) {
  visit($base . '/users/' . TestSeederConstants::ADMIN_ID)
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('TestAdmin');
});

it('shows own account settings when logged in', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::USER_ID)
    ->navigate($base . '/users/' . TestSeederConstants::USER_ID . '/account')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows 403 to guests on account settings', function () use ($base) {
  visit($base . '/users/' . TestSeederConstants::USER_ID . '/account')
    ->assertSee('403');
});
