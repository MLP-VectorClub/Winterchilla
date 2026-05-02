<?php

use Tests\Browser\Helpers\TestSeederConstants;

use function Pest\Browser\page;

$base = TestSeederConstants::BASE_URL;

it('redirects the homepage to a meaningful page for guests', function () use ($base) {
  page()->goto($base . '/');
  page()->waitForURL('**');
  expect(page()->url())->not->toBe($base . '/');
});

it('shows the color guide index page', function () use ($base) {
  page()->goto($base . '/cg');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
  expect(page()->locator('body')->innerText())->not->toContain('Uncaught');
});

it('shows the pony color guide page', function () use ($base) {
  page()->goto($base . '/cg/pony');
  page()->waitForSelector('main, #content, h1');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the episode list page', function () use ($base) {
  page()->goto($base . '/episodes');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the events list page', function () use ($base) {
  page()->goto($base . '/events');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the about page', function () use ($base) {
  page()->goto($base . '/about');
  page()->waitForSelector('h1, main');
  expect(page()->title())->not->toBeEmpty();
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the privacy policy page', function () use ($base) {
  page()->goto($base . '/about/privacy');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows a 404 page for nonexistent routes', function () use ($base) {
  $response = page()->goto($base . '/this-route-definitely-does-not-exist-xyz');
  expect($response->status())->toBe(404);
});

it('shows the sign in button for guests', function () use ($base) {
  page()->goto($base . '/episodes');
  page()->waitForSelector('body');
  $signIn = page()->locator('a[href*="da-auth"], .signin, #signin, a:has-text("Sign in"), a:has-text("Log in")');
  expect($signIn->count())->toBeGreaterThan(0);
});

it('shows the color guide full list', function () use ($base) {
  page()->goto($base . '/cg/pony/full');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the tag list page', function () use ($base) {
  page()->goto($base . '/cg/pony/tags');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});

it('shows the blending tool', function () use ($base) {
  page()->goto($base . '/cg/pony/blending');
  page()->waitForSelector('body');
  expect(page()->locator('body')->innerText())->not->toContain('Fatal error');
});
