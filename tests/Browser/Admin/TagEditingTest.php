<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;
$appearance = $base . '/cg/pony/v/' . TestSeederConstants::APPEARANCE_ID . '-Twilight-Sparkle';

it('edits and deletes a tag from the appearance page context menu', function () use ($base, $appearance) {
  $name = 'ui-tag-' . substr(md5(uniqid('', true)), 0, 6);

  // Tag the appearance through the API first (the browser login below replaces the API client's session)
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $tag = $api->post('/tags', ['name' => $name, 'type' => 'app', 'addTo' => TestSeederConstants::APPEARANCE_ID])['json'];

  try {
    $page = visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
      ->navigate($appearance)
      ->assertNoJavaScriptErrors()
      ->assertSee($name)
      ->rightClick(".tag.id-{$tag['id']}")
      ->click('a:text-is("Edit tag")')
      ->assertValue('input[name="name"]', $name)
      ->fill('input[name="name"]', $name . '-ed')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertSee($name . '-ed');

    // Deleting a tag that is in use asks twice
    $page
      ->rightClick(".tag.id-{$tag['id']}")
      ->click('a:text-is("Delete tag")')
      ->click('[data-testid="dialog-btn-confirm"]')
      ->assertSee('is currently used on')
      ->click('[data-testid="dialog-btn-confirm"]')
      ->assertDontSee($name . '-ed');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', '/tags/' . $tag['id'], ['sanityCheck' => 1]);
  }
});
