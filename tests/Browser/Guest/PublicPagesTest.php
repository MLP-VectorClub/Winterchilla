<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('redirects the homepage to a meaningful page for guests', function () use ($base) {
  visit($base . '/')
    ->assertPathIsNot('/');
});

it('shows the color guide index page', function () use ($base) {
  visit($base . '/cg')
    ->assertNoJavaScriptErrors()
    ->assertSee('Color Guide List');
});

it('shows the pony color guide page', function () use ($base) {
  visit($base . '/cg/pony')
    ->assertNoJavaScriptErrors()
    ->assertSee('Friendship is Magic Color Guide');
});

it('shows the color guide full list', function () use ($base) {
  visit($base . '/cg/pony/full')
    ->assertNoJavaScriptErrors()
    ->assertSee('Complete FiM Pony List');
});

it('shows the tag list page', function () use ($base) {
  visit($base . '/cg/pony/tags')
    ->assertNoJavaScriptErrors()
    ->assertSee('All Tags');
});

it('shows the blending tool', function () use ($base) {
  visit($base . '/cg/blending')
    ->assertNoJavaScriptErrors()
    ->assertSee('Blending');
});

it('shows the episode list page', function () use ($base) {
  visit($base . '/show')
    ->assertNoJavaScriptErrors()
    ->assertSee('TV Episodes');
});

it('shows the events list page', function () use ($base) {
  visit($base . '/events')
    ->assertNoJavaScriptErrors()
    ->assertSee('Events Archive');
});

it('shows the about page', function () use ($base) {
  visit($base . '/about')
    ->assertNoJavaScriptErrors()
    ->assertSee('MLP-VectorClub Website');
});

it('shows the privacy policy page', function () use ($base) {
  visit($base . '/about/privacy')
    ->assertNoJavaScriptErrors()
    ->assertSee('Privacy Policy');
});

it('returns 404 for nonexistent routes', function () use ($base) {
  visit($base . '/this-route-definitely-does-not-exist-xyz')
    ->assertSee('404');
});

it('shows the sign in button for guests', function () use ($base) {
  visit($base . '/cg/pony')
    ->assertSee('Sign in');
});
