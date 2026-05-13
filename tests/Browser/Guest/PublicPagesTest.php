<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('redirects the homepage to a meaningful page for guests', function () use ($base) {
  visit($base . '/')
    ->assertPathIsNot('/');
});

it('shows the color guide index page', function () use ($base) {
  visit($base . '/cg')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the pony color guide page', function () use ($base) {
  visit($base . '/cg/pony')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the color guide full list', function () use ($base) {
  visit($base . '/cg/pony/full')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the tag list page', function () use ($base) {
  visit($base . '/cg/pony/tags')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the blending tool', function () use ($base) {
  visit($base . '/cg/pony/blending')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the episode list page', function () use ($base) {
  visit($base . '/show')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the events list page', function () use ($base) {
  visit($base . '/events')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the about page', function () use ($base) {
  visit($base . '/about')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the privacy policy page', function () use ($base) {
  visit($base . '/about/privacy')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('returns 404 for nonexistent routes', function () use ($base) {
  visit($base . '/this-route-definitely-does-not-exist-xyz')
    ->assertSee('404');
});

it('shows the sign in button for guests', function () use ($base) {
  visit($base . '/cg/pony')
    ->assertSee('Sign in');
});
