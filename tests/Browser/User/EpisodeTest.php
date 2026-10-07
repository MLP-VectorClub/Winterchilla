<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('shows the episode list page', function () use ($base) {
  visit($base . '/show')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('TV Episodes');
});

it('shows the seeded episode page', function () use ($base) {
  visit($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Friendship is Magic, Part 1');
});

it('shows the seeded episode page to a logged-in user', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::USER_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Friendship is Magic, Part 1');
});

it('redirects /episode/latest to a real episode', function () use ($base) {
  visit($base . '/episode/latest')
    ->assertPathContains('/episode/');
});

it('lists the seeded episode and movie in their own tables on /show', function () use ($base) {
  visit($base . '/show')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSeeIn('#episodes', 'Friendship is Magic, Part 1')
    ->assertSeeIn('#movies', 'Equestria Girls')
    ->assertDontSeeIn('#movies', 'Friendship is Magic, Part 1');
});

it('redirects /movies to /show', function () use ($base) {
  visit($base . '/movies')
    ->assertPathIs('/show')
    ->assertSeeIn('#movies', 'Equestria Girls');
});

it('shows the seeded movie page at its canonical URL', function () use ($base) {
  visit($base . '/movie/' . TestSeederConstants::MOVIE_ID)
    ->assertPathIs('/movie/' . TestSeederConstants::MOVIE_ID . '-Equestria-Girls')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Equestria Girls')
    ->assertSee('Equestria Girls');
});

it('canonicalizes a show entry requested under the wrong type', function () use ($base) {
  visit($base . '/special/' . TestSeederConstants::MOVIE_ID)
    ->assertPathIs('/movie/' . TestSeederConstants::MOVIE_ID . '-Equestria-Girls');
});

it('404s for a show entry that does not exist', function () use ($base) {
  visit($base . '/movie/999999')
    ->assertSee('404');
});

it('shows show-entry admin controls on /show for staff', function () use ($base) {
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/show')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertPresent('#add-show')
    ->assertPresent('#movies .edit-show')
    ->assertPresent('#movies .delete-show');
});

it('hides show-entry admin controls on /show from guests', function () use ($base) {
  visit($base . '/show')
    ->assertMissing('#add-show')
    ->assertMissing('#movies .edit-show');
});
