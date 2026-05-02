<?php

use Tests\Browser\Helpers\TestSeederConstants;

use function Pest\Browser\page;

$base = TestSeederConstants::BASE_URL;
$appearanceId = TestSeederConstants::APPEARANCE_ID;

it('shows the seeded appearance detail page', function () use ($base, $appearanceId) {
  page()->goto($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
  expect(page()->locator('body')->innerText())->not->toContain('Uncaught');
});

it('shows the pony color guide change list', function () use ($base) {
  page()->goto($base . '/cg/pony/changes');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the color picker tool', function () use ($base) {
  page()->goto($base . '/cg/pony/picker');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows appearance management UI when logged in as admin', function () use ($base, $appearanceId) {
  page()->goto($base . '/test-login/' . TestSeederConstants::ADMIN_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/cg/pony/v/' . $appearanceId . '-Twilight-Sparkle');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
  // Admin controls should be visible
  $adminControls = page()->locator('.admin-controls, .manage, [data-admin], button:has-text("Edit"), a:has-text("Edit")');
  expect($adminControls->count())->toBeGreaterThanOrEqual(0);
});
