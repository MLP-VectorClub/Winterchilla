<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('shows 403 to guests on the admin panel', function () use ($base) {
  visit($base . '/admin')
    ->assertSee('403');
});

it('shows the admin panel to admins', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/admin')
    ->assertPathIs('/admin')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the admin logs page', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/admin/logs')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the useful links admin page', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/admin/usefullinks')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the admin notices page', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/admin/notices')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the PCG appearances admin page', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/admin/pcg-appearances')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the Discord admin page', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/admin/discord')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the logs page with type filtering', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/logs')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});
