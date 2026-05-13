<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('shows the episode page without fatal errors', function () use ($base) {
  visit($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});

it('shows the episode page for a logged-in user without fatal errors', function () use ($base) {
  visit($base . '/test-login/' . TestSeederConstants::USER_ID)
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()
    ->assertDontSee('Fatal error');
});
