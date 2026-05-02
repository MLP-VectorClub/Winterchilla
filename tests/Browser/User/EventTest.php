<?php

use Tests\Browser\Helpers\TestSeederConstants;

use function Pest\Browser\page;

$base    = TestSeederConstants::BASE_URL;
$eventId = TestSeederConstants::EVENT_ID;

it('shows the events list page', function () use ($base) {
  page()->goto($base . '/events');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the seeded event detail page', function () use ($base, $eventId) {
  page()->goto($base . '/event/' . $eventId);
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows event detail for a logged-in user', function () use ($base, $eventId) {
  page()->goto($base . '/test-login/' . TestSeederConstants::USER_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/event/' . $eventId);
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});
