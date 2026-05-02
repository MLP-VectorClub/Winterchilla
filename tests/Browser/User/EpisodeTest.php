<?php

use Tests\Browser\Helpers\TestSeederConstants;

use function Pest\Browser\page;

$base = TestSeederConstants::BASE_URL;

it('shows the episode list page', function () use ($base) {
  page()->goto($base . '/episodes');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the seeded episode page', function () use ($base) {
  page()->goto($base . '/episode/' . TestSeederConstants::SHOW_ID);
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('redirects /episode/latest to a real episode', function () use ($base) {
  page()->goto($base . '/episode/latest');
  page()->waitForURL('**/episode/**');
  expect(page()->url())->toContain('/episode/');
});
