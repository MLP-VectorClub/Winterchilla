<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base    = TestSeederConstants::BASE_URL;
$eventId = TestSeederConstants::EVENT_ID;

it('shows the events list page', function () use ($base) {
  visit($base . '/events')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the seeded event detail page', function () use ($base, $eventId) {
  visit($base . '/event/' . $eventId)
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('Test Coloring Event');
});

it('shows event detail for a logged-in user', function () use ($base, $eventId) {
  visit($base . '/test-login/' . TestSeederConstants::USER_ID)
    ->navigate($base . '/event/' . $eventId)
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error')
    ->assertSee('Test Coloring Event');
});
