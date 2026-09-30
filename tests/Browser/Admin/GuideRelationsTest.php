<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('links an appearance to a show in the guide relations editor of an episode page', function () use ($base) {
  try {
    visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertNoJavaScriptErrors()
      ->assertMissing('section.appearances')
      ->click('#cg-relations')
      ->select('#guide-relation-editor .split-select:last-child select', (string)TestSeederConstants::APPEARANCE_ID)
      ->click('#guide-relation-editor button[title="Link selected"]')
      ->click('[data-testid="dialog-btn-save"]')
      // The section the API rendered is inserted into the page
      ->assertPresent('section.appearances')
      ->assertSee('Twilight Sparkle');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('PUT', '/show/' . TestSeederConstants::SHOW_ID . '/guide-relations', ['ids' => '']);
  }
});

it('links a show to an appearance in the show relations editor of an appearance page', function () use ($base) {
  $id = TestSeederConstants::APPEARANCE_ID;

  try {
    visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
      ->navigate($base . '/cg/pony/v/' . $id . '-Twilight-Sparkle')
      ->assertNoJavaScriptErrors()
      ->assertMissing('#related-shows p a')
      ->click('.edit-show-relations')
      ->select('#show-relation-editor .split-select:last-child select', (string)TestSeederConstants::SHOW_ID)
      ->click('#show-relation-editor button[title="Link selected"]')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertPresent('#related-shows p a')
      ->assertSee('Friendship is Magic, Part 1');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('PUT', "/cg/appearance/$id/guide-relations", ['ids' => '']);
  }
});
