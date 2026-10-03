<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('adds, edits and deletes a useful link through the admin page', function () use ($base) {
  $label = 'UI Link ' . substr(md5(uniqid('', true)), 0, 5);

  $page = visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/admin/usefullinks')
    ->assertNoJavaScriptErrors()
    ->click('#add-link')
    ->fill('input[name="label"]', $label)
    ->fill('input[name="url"]', '/about')
    ->select('select[name="minRole"]', 'guest')
    ->click('[data-testid="dialog-btn-add"]')
    // The page reloads after saving
    ->assertSee($label);

  // Edit it: the form is filled from the API response
  $page
    ->click("li:has-text(\"$label\") .edit-link")
    ->assertValue('input[name="label"]', $label)
    ->fill('input[name="label"]', $label . ' II')
    ->click('[data-testid="dialog-btn-save-changes"]')
    ->assertSee($label . ' II');

  // And delete it again
  $page
    ->click("li:has-text(\"$label II\") .delete-link")
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertDontSee($label . ' II');
});
