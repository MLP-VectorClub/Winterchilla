<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('links an appearance to a show in the guide relations editor of an episode page', function () use ($base) {
  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
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
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('PUT', '/show/' . TestSeederConstants::SHOW_ID . '/appearances', ['ids' => '']);
  }
})->group('winterchilla-only');

it('links a show to an appearance in the show relations editor of an appearance page', function () use ($base) {
  $id = TestSeederConstants::APPEARANCE_ID;

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $id . '-Twilight-Sparkle')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertMissing('#related-shows p a')
      ->click('.edit-show-relations')
      ->select('#show-relation-editor .split-select:last-child select', (string)TestSeederConstants::SHOW_ID)
      ->click('#show-relation-editor button[title="Link selected"]')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertPresent('#related-shows p a')
      ->assertSee('Friendship is Magic, Part 1');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('PUT', "/appearances/$id/shows", ['ids' => '']);
  }
})->group('winterchilla-only');
