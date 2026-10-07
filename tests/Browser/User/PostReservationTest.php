<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('reserves a request and cancels the reservation from the episode page', function () use ($base) {
  $post = '#post-' . TestSeederConstants::POST_ID;

  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Seeded Test Request')
    ->click("$post .reserve-request")
    // The list item is replaced with the one the API returned, which now offers to cancel the reservation
    ->assertPresent("$post .cancel")
    ->click("$post .cancel")
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertPresent("$post .reserve-request")
    ->assertMissing("$post .cancel");
});
