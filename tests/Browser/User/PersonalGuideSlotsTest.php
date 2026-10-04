<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();
$userId = TestSeederConstants::FRESH_USER_ID;

// Every user starts with the free slot (10 points, which is what one personal appearance costs) and may create
// personal appearances. FreshUser has done nothing yet, so it shows what a new user gets.

function pcgMake(bool $on):void {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  expect($admin->request('PUT', '/users/' . TestSeederConstants::FRESH_USER_ID . '/preferences/a_pcgmake', ['value' => $on ? '1' : '0'])['status'])->toBe(200);
}

it('gives a new user one personal guide appearance and then reports no slots left', function () use ($base, $userId) {
  $label = 'Fresh Pony ' . substr(md5(uniqid('', true)), 0, 5);

  try {
    $page = visit(TestSeederConstants::loginUrl($userId))
      ->navigate($base . "/users/$userId/cg")
      ->assertNoJavaScriptErrors()
      // The free slot lets the editor open, and saving takes the user to the new appearance's page
      ->click('#new-appearance-btn')
      ->fill('[data-testid="form-label-input"]', $label)
      ->click('[data-testid="dialog-btn-save"]')
      ->assertPathContains('/cg/v/')
      ->assertSee($label);

    // That used the slot: the next attempt is turned down with an explanation
    $page
      ->navigate($base . "/users/$userId/cg")
      ->click('#new-appearance-btn')
      ->assertSee('slots');
  }
  finally {
    // Delete the appearance again (found through the API); the slot comes back
    $owner = ApiClient::loggedInAs($userId);
    foreach ($owner->get("/users/$userId/personal-guide/appearances", ['size' => 100])['json']['appearances'] ?? [] as $appearance) {
      if (($appearance['label'] ?? null) === $label)
        $owner->request('DELETE', '/appearances/' . $appearance['id']);
    }
  }
});

it('says when personal guide appearances are switched off for a user', function () use ($base, $userId) {
  pcgMake(false);
  try {
    visit(TestSeederConstants::loginUrl($userId))
      ->navigate($base . "/users/$userId/cg")
      ->click('#new-appearance-btn')
      ->assertSee('not allowed to create');
  }
  finally {
    pcgMake(true);
  }
});
