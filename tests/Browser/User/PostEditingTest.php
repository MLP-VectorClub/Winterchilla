<?php

use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('clears the broken status of a post from its edit dialog', function () use ($base) {
  $post = '#post-' . TestSeederConstants::BROKEN_UI_POST_ID;

  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()
    ->assertPresent("$post .broken-note")
    ->click("$post .edit")
    ->click('#dialog-clear-broken-status')
    // The list item is replaced with the one the API returned, without the note
    ->assertMissing("$post .broken-note");
});

it('replaces the image of a post from its edit dialog', function () use ($base) {
  $post = '#post-' . TestSeederConstants::POST_ID;

  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->click("$post .edit")
    ->click('#dialog-update-image')
    ->fill('#img-update-form input[name="imageUrl"]', 'http://fav.me/dfin007')
    ->click('[data-testid="dialog-btn-update"]')
    ->assertSee('Image has been updated');
});
