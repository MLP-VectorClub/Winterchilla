<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base    = TestSeederConstants::BASE_URL;
$userId  = TestSeederConstants::USER_ID;
$adminId = TestSeederConstants::ADMIN_ID;

it('shows a guest the personal color guide list', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee("TestUser's Personal Color Guide");
});

it('shows the owner their own personal color guide list', function () use ($base, $userId) {
  visit($base . '/test-login/' . $userId)
    ->navigate($base . '/users/' . $userId . '/cg')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee("TestUser's Personal Color Guide");
});

it('shows the owner their own point history page', function () use ($base, $userId) {
  visit($base . '/test-login/' . $userId)
    ->navigate($base . '/users/' . $userId . '/cg/point-history')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('Your Point History');
});

it('shows the owner their own slot history page', function () use ($base, $userId) {
  visit($base . '/test-login/' . $userId)
    ->navigate($base . '/users/' . $userId . '/cg/slot-history')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('Your Point History');
});

it('lets staff view another user\'s point history', function () use ($base, $userId, $adminId) {
  visit($base . '/test-login/' . $adminId)
    ->navigate($base . '/users/' . $userId . '/cg/point-history')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee("TestUser's Point History");
});

it('denies a guest access to a user\'s point history', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg/point-history')
    ->assertSee('403');
});
