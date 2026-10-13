<?php

use Tests\Browser\Helpers\TestSeederConstants;

// The seed holds every state the UI has something to show for (see TestSeeder::seedEveryState), so the pages can be looked at, and
// the visual and icon audits (Celestia/scripts/ui-audit) find something on each of them. These tests fail when the seed loses one.

$base = TestSeederConstants::baseUrl();

it('shows the reservations in every state on the episode page', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Recently Reserved Request')
    ->assertSee('Overdue Reserved Request')
    ->assertSee('Reservation Without A Request')
    ->assertSee('Awaiting Approval Request')
    ->assertSee('Approved Request');
});

it('lists tags of every type, an alias and a stale use count', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/cg/pony/tags')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('twilight sparkle')
    ->assertSee('twily')
    ->assertSee('stale count')
    ->assertSee('Character')->assertSee('Species')->assertSee('Gender')->assertSee('Clothing')->assertSee('Category')->assertSee('Warning');
});

it('shows the colors and notes of a seeded appearance', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/cg/pony/v/5-Rainbow-Dash')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Rainbow Dash')
    ->assertSee('Coat')->assertSee('Mane')->assertSee('Fastest flyer');
});

it('shows the rating of an episode that has votes and the next one on its page', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::USER_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::VOTED_SHOW_ID)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('The Return of Harmony')->assertSee('Lesson Zero')->assertSee('4.5');
});

it('lists the notice and the useful links', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/cg')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('the club meets on Fridays');
});
