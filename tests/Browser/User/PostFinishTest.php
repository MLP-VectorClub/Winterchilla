<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\ClubGallery;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('reserves, finishes and approves a request from the episode page', function () use ($base) {
  $post = '#post-' . TestSeederConstants::POST_ID;

  try {
    $page = visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertNoJavaScriptErrors()
      ->click("$post .reserve-request")
      ->assertPresent("$post .finish")
      // Mark it finished with a deviation the test setup knows about
      ->click("$post .finish")
      ->fill('input[name="deviation"]', 'http://fav.me/dfin003')
      ->click('[data-testid="dialog-btn-finish"]')
      ->assertSee('has been marked as finished')
      // The requester is told (the dialog stays open until it is closed)
      ->assertSee('TestUser has been notified')
      ->click('[data-testid="dialog-btn-close"]')
      ->assertPresent("$post .check");

    // The group gallery doesn't have it yet
    $page->click("$post .check")
      ->assertSee('has not been submitted to/accepted by the group yet')
      ->click('[data-testid="dialog-btn-close"]');

    ClubGallery::accept('dfin003');
    $page->click("$post .check")
      ->assertSee('appears to be in the group gallery');
  }
  finally {
    ClubGallery::reject('dfin003');

    // Put the seeded request back the way it was for the other tests: unlock, unfinish and unreserve it
    $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
    $admin->request('DELETE', '/posts/' . TestSeederConstants::POST_ID . '/approval');
    $admin->request('DELETE', '/posts/' . TestSeederConstants::POST_ID . '/finish');
    $admin->request('DELETE', '/posts/' . TestSeederConstants::POST_ID . '/reservation');
  }
});
