<?php

use Tests\Browser\Helpers\TestSeederConstants;

use function Pest\Browser\page;

$base = TestSeederConstants::BASE_URL;

it('denies the admin panel to guests', function () use ($base) {
  $response = page()->goto($base . '/admin');
  expect($response->status())->toBeIn([302, 401, 403]);
});

it('shows the admin panel to admins', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::ADMIN_ID);
  page()->waitForURL('**/');

  $response = page()->goto($base . '/admin');
  page()->waitForSelector('body');
  expect($response->status())->toBe(200);
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the admin logs page', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::ADMIN_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/admin/logs');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the useful links admin page', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::ADMIN_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/admin/usefullinks');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the admin notices page', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::ADMIN_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/admin/notices');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the PCG appearances admin page', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::ADMIN_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/admin/pcg-appearances');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the Discord admin page', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::ADMIN_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/admin/discord');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the logs page with type filtering', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::ADMIN_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/logs');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});
