<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('redirects /eqg/[id] to the movie page', function () use ($base) {
  visit($base . '/eqg/' . TestSeederConstants::MOVIE_ID)
    ->assertNoJavaScriptErrors()
    ->assertPathBeginsWith('/movie/' . TestSeederConstants::MOVIE_ID)
    ->assertSee('Equestria Girls');
});

it('redirects /eqg/[name] to the movie slug URL', function () use ($base) {
  // The slug doesn't exist in the seed data, but the redirect target must be the slug URL
  visit($base . '/eqg/2')
    ->assertPathBeginsWith('/movie/');
  visit($base . '/eqg/friendship-games')
    ->assertPathBeginsWith('/movie/equestria-girls-friendship-games');
});

it('redirects a post share link to the post on its show page', function () use ($base) {
  visit($base . '/s/' . base_convert((string)TestSeederConstants::POST_ID, 10, 36))
    ->assertNoJavaScriptErrors()
    ->assertSee('Seeded Test Request');
});

it('404s on unknown or malformed share links', function () use ($base) {
  visit($base . '/s/zzzzz')->assertSee('404');
  visit($base . '/s/req/999999')->assertSee('404');
  visit($base . '/s/bogus/1')->assertSee('404');
});

it('shows the browser recognition page', function () use ($base) {
  visit($base . '/about/browser')
    ->assertNoJavaScriptErrors()
    ->assertSee('Browser recognition test page');
});

it('hides the browser recognition page of a session from non-developers', function () use ($base) {
  visit($base . '/about/browser/1')->assertSee('403');
});

it('shows the components page', function () use ($base) {
  visit($base . '/components')
    ->assertNoJavaScriptErrors()
    ->assertSee('Components');
});

it('serves the API docs page', function () use ($base) {
  visit($base . '/docs')
    ->assertSee('API');
});

it('serves the muffin rating image as an SVG', function () use ($base) {
  $ch = curl_init($base . '/muffin-rating?w=42');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]);
  $body = curl_exec($ch);
  expect(curl_getinfo($ch, CURLINFO_HTTP_CODE))->toBe(200)
    ->and(curl_getinfo($ch, CURLINFO_CONTENT_TYPE))->toStartWith('image/svg+xml')
    ->and($body)->toContain("width='42'");
});
