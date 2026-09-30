<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base    = TestSeederConstants::BASE_URL;
$eventId = TestSeederConstants::EVENT_ID;

it('shows the events list page', function () use ($base) {
  visit($base . '/events')
    ->assertNoJavaScriptErrors()
    ->assertSee('Events Archive');
});

it('shows the seeded event detail page', function () use ($base, $eventId) {
  visit($base . '/event/' . $eventId)
    ->assertNoJavaScriptErrors()
    ->assertSee('Test Coloring Event')
    ->assertSee('Seeded Entry');
});

it('shows event detail for a logged-in user', function () use ($base, $eventId) {
  visit($base . '/test-login/' . TestSeederConstants::USER_ID)
    ->navigate($base . '/event/' . $eventId)
    ->assertNoJavaScriptErrors()
    ->assertSee('Test Coloring Event')
    ->assertSee('Test Coloring Event');
});

it('lets an entrant edit and withdraw their entries on the event page', function () use ($base, $eventId) {
  $page = visit($base . '/test-login/' . TestSeederConstants::USER_ID)
    ->navigate($base . '/event/' . $eventId)
    ->assertNoJavaScriptErrors()
    // Edit: the form is filled from the API, saving swaps in the list item it returns
    ->click('#entry-' . TestSeederConstants::EVENT_ENTRY_ID . ' .edit-entry')
    ->assertValue('input[name="title"]', 'Seeded Entry')
    ->fill('input[name="title"]', 'Retitled Entry')
    ->click('[data-testid="dialog-btn-save"]')
    ->assertSee('Retitled Entry');

  // Withdraw another one
  $page
    ->click('#entry-' . TestSeederConstants::EVENT_ENTRY_UI_DELETE_ID . ' .delete-entry')
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertMissing('#entry-' . TestSeederConstants::EVENT_ENTRY_UI_DELETE_ID);
});
