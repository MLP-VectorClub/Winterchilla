<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;
$userId = TestSeederConstants::FRESH_USER_ID;

// Every user starts with the free slot (10 points, which is what one personal appearance costs) and may create
// personal appearances. FreshUser has done nothing yet, so it shows what a new user gets.

function pcgMake(bool $on):void {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  expect($admin->request('PUT', '/user/' . TestSeederConstants::FRESH_USER_ID . '/preference/a_pcgmake', ['value' => $on ? '1' : '0'])['status'])->toBe(200);
}

it('gives a new user one personal guide appearance and then reports no slots left', function () use ($base, $userId) {
  $label = 'Fresh Pony ' . substr(md5(uniqid('', true)), 0, 5);

  try {
    $page = visit($base . '/test-login/' . $userId)
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
      ->assertSee('no available slots left');
  }
  finally {
    // Delete the appearance again (found on the user's personal guide page); the slot comes back
    $owner = ApiClient::loggedInAs($userId);
    if (preg_match('~/v/(\d+)-' . preg_quote(str_replace(' ', '-', $label), '~') . '~', $owner->page("/users/$userId/cg"), $m))
      $owner->request('DELETE', '/cg/appearance/' . $m[1]);
  }
});

it('says when personal guide appearances are switched off for a user', function () use ($base, $userId) {
  pcgMake(false);
  try {
    visit($base . '/test-login/' . $userId)
      ->navigate($base . "/users/$userId/cg")
      ->click('#new-appearance-btn')
      ->assertSee('not allowed to create personal color guide appearances');
  }
  finally {
    pcgMake(true);
  }
});
