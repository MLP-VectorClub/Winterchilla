<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('lets a signed-in user rate an aired episode from its page', function () use ($base) {
  // The admin hasn't voted on the seeded episode (the API tests vote as the regular user)
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()
    ->click('#voting .rate')
    ->click('#star-rating .rate label:nth-child(4)')
    ->click('[data-testid="dialog-btn-rate"]')
    ->assertSee('Your rating: 4 muffins');
});
