<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('shows the episode list page', function () use ($base) {
  visit($base . '/show')
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the seeded episode page', function () use ($base) {
  visit($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('redirects /episode/latest to a real episode', function () use ($base) {
  visit($base . '/episode/latest')
    ->assertPathContains('/episode/');
});
