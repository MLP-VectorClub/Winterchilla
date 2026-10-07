<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('shows 403 to guests on the admin panel', function () use ($base) {
  visit($base . '/admin')
    ->assertSee('403');
});

it('shows the admin panel to admins', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/admin')
    ->assertPathIs('/admin')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Admin Area');
});

it('shows the admin logs page', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/admin/logs')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Global logs');
});

it('shows the useful links admin page', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/admin/usefullinks')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Manage useful links');
});

it('shows the admin notices page', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/admin/notices')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Manage notices');
});

it('shows the PCG appearances admin page', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/admin/pcg-appearances')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('All PCG appearances');
});

it('shows the logs page with type filtering', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/logs')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Global logs');
});
