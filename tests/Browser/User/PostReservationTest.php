<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('reserves a request and cancels the reservation from the episode page', function () use ($base) {
  $post = '#post-' . TestSeederConstants::POST_ID;

  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()
    ->assertSee('Seeded Test Request')
    ->click("$post .reserve-request")
    // The list item is replaced with the one the API returned, which now offers to cancel the reservation
    ->assertPresent("$post .cancel")
    ->click("$post .cancel")
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertPresent("$post .reserve-request")
    ->assertMissing("$post .cancel");
});
