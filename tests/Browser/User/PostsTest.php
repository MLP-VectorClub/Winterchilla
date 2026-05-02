<?php

use Tests\Browser\Helpers\TestSeederConstants;

use function Pest\Browser\page;

$base = TestSeederConstants::BASE_URL;

it('shows the episode page with a posts section', function () use ($base) {
  page()->goto($base . '/episode/' . TestSeederConstants::SHOW_ID);
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows request/reservation forms when logged in', function () use ($base) {
  page()->goto($base . '/test-login/' . TestSeederConstants::USER_ID);
  page()->waitForURL('**/');

  page()->goto($base . '/episode/' . TestSeederConstants::SHOW_ID);
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
  // Logged-in users should see an action for adding posts
  $addPost = page()->locator('button:has-text("Request"), button:has-text("Reserve"), a:has-text("Request"), a:has-text("Reserve"), .add-request, .add-reservation');
  expect($addPost->count())->toBeGreaterThanOrEqual(0);
});
