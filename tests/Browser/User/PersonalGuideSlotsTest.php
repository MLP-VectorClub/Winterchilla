<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;
$userId = TestSeederConstants::USER_ID;

// Staff-only settings of the regular user, changed through the API (each call signs the admin in again, which is cheap
// and keeps the helpers independent of the browser's own admin session)
function setPcgMake(bool $on):void {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  expect($admin->request('PUT', '/user/' . TestSeederConstants::USER_ID . '/preference/a_pcgmake', ['value' => $on ? '1' : '0'])['status'])->toBe(200);
}

function givePoints(int $amount):void {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  expect($admin->post('/user/' . TestSeederConstants::USER_ID . '/pcg/points', ['amount' => $amount, 'comment' => 'Slots UI test'])['status'])->toBe(201);
}

it('says when a user has no personal guide slots left', function () use ($base, $userId) {
  // The seeded personal appearances use up more slots than the user has
  setPcgMake(true);
  try {
    visit($base . '/test-login/' . $userId)
      ->navigate($base . "/users/$userId/cg")
      ->assertNoJavaScriptErrors()
      ->click('#new-appearance-btn')
      ->assertSee('no available slots left');
  }
  finally {
    setPcgMake(false);
  }
});

it('says when personal guide appearances are switched off for a user', function () use ($base, $userId) {
  // Points can't be taken back below 10 in total, so the user keeps the ones given here (the tests that follow don't
  // depend on the user having none; the "no slots left" test above runs first on purpose)
  givePoints(100);
  setPcgMake(false);
  visit($base . '/test-login/' . $userId)
    ->navigate($base . "/users/$userId/cg")
    ->click('#new-appearance-btn')
    ->assertSee('not allowed to create personal color guide appearances');
});

it('lets a user with enough points add a personal guide appearance', function () use ($base, $userId) {
  $label = 'Slot Pony ' . substr(md5(uniqid('', true)), 0, 5);
  givePoints(100);
  setPcgMake(true);

  try {
    visit($base . '/test-login/' . $userId)
      ->navigate($base . "/users/$userId/cg")
      ->assertNoJavaScriptErrors()
      // The slot check passes, so the editor opens
      ->click('#new-appearance-btn')
      ->fill('[data-testid="form-label-input"]', $label)
      ->click('[data-testid="dialog-btn-save"]')
      // Saving takes the user to the new appearance's page
      ->assertPathContains('/cg/v/')
      ->assertSee($label);
  }
  finally {
    // Delete the new appearance again (found on the user's personal guide page) and restore the user's settings
    $owner = ApiClient::loggedInAs($userId);
    if (preg_match('~/v/(\d+)-' . preg_quote(str_replace(' ', '-', $label), '~') . '~', $owner->page("/users/$userId/cg"), $m))
      $owner->request('DELETE', '/cg/appearance/' . $m[1]);

    setPcgMake(false);
  }
});
