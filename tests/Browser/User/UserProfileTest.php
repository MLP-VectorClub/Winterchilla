<?php

use Tests\Browser\Helpers\TestSeederConstants;

use function Pest\Browser\page;

$base = TestSeederConstants::BASE_URL;

it('shows the profile page of the seeded regular user', function () use ($base) {
  page()->goto($base . '/user/' . TestSeederConstants::USER_ID);
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the profile page of the seeded admin user', function () use ($base) {
  page()->goto($base . '/user/' . TestSeederConstants::ADMIN_ID);
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows own account settings when logged in', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::USER_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/user/' . TestSeederConstants::USER_ID . '/settings');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('denies settings page to guests with 403 or redirect', function () use ($base) {
  $response = page()->goto($base . '/user/' . TestSeederConstants::USER_ID . '/settings');
  expect($response->status())->toBeIn([302, 401, 403]);
});
