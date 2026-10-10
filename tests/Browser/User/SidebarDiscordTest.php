<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

// Who is on the Discord server is covered by the API tests (/users/me); here the sidebar's own decisions
it('invites guests to the Discord server', function () use ($base) {
  visit($base . '/show')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSeeIn('#sidebar', 'Join Discord');
});

it('invites signed in users who are not on the server, until they hide the invitation', function () use ($base) {
  $id = TestSeederConstants::USER_ID;
  $api = ApiClient::loggedInAs($id);

  try {
    $page = visit(TestSeederConstants::loginUrl($id))
      ->navigate($base . '/show')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSeeIn('#sidebar', 'Join Discord');

    expect($api->json('PUT', "/users/$id/preferences/p_hidediscord", ['value' => 1])['status'])->toBeIn([200, 204]);

    $page->navigate($base . '/show')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSeeIn('#sidebar', 'Sign out')
      ->assertDontSeeIn('#sidebar', 'Join Discord');
  }
  finally {
    $api->json('PUT', "/users/$id/preferences/p_hidediscord", ['value' => 0]);
  }
})->skip(fn() => !TestSeederConstants::external(), 'Celestia only')->group('celestia-only');
