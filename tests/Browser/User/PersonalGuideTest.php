<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base    = TestSeederConstants::baseUrl();
$userId  = TestSeederConstants::USER_ID;
$adminId = TestSeederConstants::ADMIN_ID;

it('shows a guest the personal color guide list', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg')
    ->assertNoJavaScriptErrors()
    ->assertSee('Personal Color Guide')
    ->assertSee("TestUser's Personal Color Guide");
});

it('shows the owner their own personal color guide list', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg')
    ->assertNoJavaScriptErrors()
    ->assertSee('Personal Color Guide')
    ->assertSee("TestUser's Personal Color Guide");
});

it('shows the owner their own point history page', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg/point-history')
    ->assertNoJavaScriptErrors()
    ->assertSee('Point History')
    ->assertSee('Your Point History');
});

it('shows the owner their own slot history page', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg/slot-history')
    ->assertNoJavaScriptErrors()
    ->assertSee('Point History')
    ->assertSee('Your Point History');
});

it('lets staff view another user\'s point history', function () use ($base, $userId, $adminId) {
  visit(TestSeederConstants::loginUrl($adminId))
    ->navigate($base . '/users/' . $userId . '/cg/point-history')
    ->assertNoJavaScriptErrors()
    ->assertSee('Point History')
    ->assertSee("TestUser's Point History");
});

it('denies a guest access to a user\'s point history', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg/point-history')
    ->assertSee('403');
});

it('lists the private personal appearance to guests only as a locked entry', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg')
    ->assertNoJavaScriptErrors()
    ->assertSee('Personal Test Pony')
    ->assertSee('Private Test Pony')
    ->assertPresent('.typcn-lock-closed');
});

it('hides the private personal appearance page from guests unless they have its token', function () use ($base, $userId) {
  $path = '/users/' . $userId . '/cg/v/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID . '-Private-Test-Pony';

  visit($base . $path)->assertSee('403');

  visit($base . $path . '?token=' . TestSeederConstants::PRIVATE_PERSONAL_TOKEN)
    ->assertNoJavaScriptErrors()
    ->assertSee('Private Test Pony');
})->group('winterchilla-only');

it('shows the owner and staff the private personal appearance too', function () use ($base, $userId, $adminId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg')
    ->assertSee('Personal Test Pony')
    ->assertSee('Private Test Pony');

  visit(TestSeederConstants::loginUrl($adminId))
    ->navigate($base . '/users/' . $userId . '/cg')
    ->assertSee('Private Test Pony');
});

it('shows a personal appearance page with its color group', function () use ($base, $userId) {
  visit($base . '/users/' . $userId . '/cg/v/' . TestSeederConstants::PERSONAL_APPEARANCE_ID . '-Personal-Test-Pony')
    ->assertNoJavaScriptErrors()
    ->assertSee('Personal Test Pony')
    ->assertSee('Personal Coat')
    ->assertSee('Personal Base');
});

it('shows the point history with the seeded grant to the owner', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg/point-history')
    ->assertNoJavaScriptErrors()
    ->assertSee('Seeded contract test grant');
});

it('lets the owner open their private personal appearance page', function () use ($base, $userId) {
  visit(TestSeederConstants::loginUrl($userId))
    ->navigate($base . '/users/' . $userId . '/cg/v/' . TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID . '-Private-Test-Pony')
    ->assertNoJavaScriptErrors()
    ->assertSee('Private Test Pony');
});

it('lets staff give personal guide points from a user\'s profile', function () use ($base, $userId, $adminId) {
  visit(TestSeederConstants::loginUrl($adminId))
    ->navigate($base . '/users/' . $userId)
    ->assertNoJavaScriptErrors()
    ->click('#give-pcg-points')
    // The seeded personal appearances use up more slots than the user has, so the form requires topping them up first
    ->fill('input[name="amount"]', '15')
    ->click('[data-testid="dialog-btn-continue"]')
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertSee("You've successfully given 15 points to TestUser");
});

