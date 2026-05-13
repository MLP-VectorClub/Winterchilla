<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base         = TestSeederConstants::BASE_URL;
$appearanceId = TestSeederConstants::APPEARANCE_ID;

it('shows the seeded appearance detail page', function () use ($base, $appearanceId) {
  visit($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('Twilight Sparkle');
});

it('shows the pony color guide change list', function () use ($base) {
  visit($base . '/cg/pony/changes')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the color picker tool', function () use ($base) {
  visit($base . '/cg/pony/picker')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows admin controls on appearance page when logged in as admin', function () use ($base, $appearanceId) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('Twilight Sparkle');
});

it('shows the new appearance button on guide page for admins', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});
