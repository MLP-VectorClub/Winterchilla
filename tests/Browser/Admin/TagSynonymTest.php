<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('makes a tag a synonym of another and removes the synonym again from the tag list', function () use ($base) {
  $suffix = substr(md5(uniqid('', true)), 0, 6);
  $sourceName = "syn-source-$suffix";
  $targetName = "syn-target-$suffix";

  // Set the tags up through the API first: logging in as the admin in the browser replaces the API client's session
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $source = $api->post('/tags', ['name' => $sourceName, 'type' => 'app'])['json'];
  $target = $api->post('/tags', ['name' => $targetName, 'type' => 'app'])['json'];

  try {
    $page = visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSee($sourceName)
      // Make the source tag a synonym of the target
      ->click("tr:has-text(\"$sourceName\") button.synon")
      ->select('select[name="targetId"]', (string)$target['id'])
      ->click('[data-testid="dialog-btn-make-synonym"]')
      ->assertSee('Tag synonyms created')
      ->click('[data-testid="dialog-btn-reload"]')
      ->assertSee("Synonym of $targetName");

    // Trying to make it a synonym again offers to remove the existing synonym instead
    $page
      ->click("tr:has-text(\"$sourceName\") button.synon")
      ->assertSee('already a synonym of')
      ->click('[data-testid="dialog-btn-confirm"]')
      ->assertSee('Preserve current tag connections')
      ->click('[data-testid="dialog-btn-remove-synonym"]')
      ->click('[data-testid="dialog-btn-reload"]')
      ->assertDontSee("Synonym of $targetName");
  }
  finally {
    $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
    $api->request('DELETE', '/tags/' . $source['id']);
    $api->request('DELETE', '/tags/' . $target['id']);
  }
})->group('winterchilla-only');

it('recounts tag uses from the refresh buttons of the tag list', function () use ($base) {
  $name = 'refresh-tag-' . substr(md5(uniqid('', true)), 0, 6);
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $tag = $api->post('/tags', ['name' => $name, 'type' => 'app', 'addTo' => TestSeederConstants::APPEARANCE_ID])['json'];

  try {
    $page = visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      // One tag: its own refresh button updates the count in place
      ->click("tr:has-text(\"$name\") button.refresh")
      ->assertSeeIn("tr:has-text(\"$name\") td.uses", '1');

    // And the one in the header, for every tag on the page, which reports the outcome
    $page
      ->click('thead .refresh-all')
      ->assertSee('use count was updated');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', '/tags/' . $tag['id'], ['sanityCheck' => 1]);
  }
})->group('winterchilla-only');
