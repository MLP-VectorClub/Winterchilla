<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('shows a server-side validation error from the API in the error dialog', function () use ($base) {
  // "Twilight Sparkle" already exists in the pony guide; the server answers 422 and the shim hands the message to the
  // dialog (previously it was HTML with a link, now it is plain text with the URL in brackets)
  visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
    ->navigate($base . '/cg/pony')
    ->click('[data-testid="create-appearance-btn"]')
    ->fill('[data-testid="form-label-input"]', 'Twilight Sparkle')
    ->click('[data-testid="dialog-btn-save"]')
    ->assertSee('already exists in the')
    ->assertSee('/cg/pony/v/');
});
