<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\ClubGallery;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('reserves, finishes and approves a request from the episode page', function () use ($base) {
  $post = '#post-' . TestSeederConstants::POST_ID;

  try {
    $page = visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
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
})->group('winterchilla-only');


// The dialogs that follow finishing a post (what they say, whether they stay open) and the group gallery check button are
// implementation details; what has to hold everywhere is that the post ends up finished with that deviation.
it('reserves and finishes a request from the episode page', function () use ($base) {
  $post = '#post-' . TestSeederConstants::POST_ID;
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->click("$post .reserve-request")
      ->assertPresent("$post .finish")
      ->click("$post .finish")
      ->fill('input[name="deviation"]', 'http://fav.me/dfin003')
      ->click('[data-testid="dialog-btn-finish"]')
      ->wait(2);

    $r = $admin->get('/posts', ['showId' => TestSeederConstants::SHOW_ID, 'kind' => 'request']);
    $posts = array_column($r['json']['posts'], null, 'id');
    expect($posts[TestSeederConstants::POST_ID]['deviationId'])->toBe('dfin003')
      ->and($posts[TestSeederConstants::POST_ID]['finishedAt'])->not->toBeNull();
  }
  finally {
    $admin->request('DELETE', '/posts/' . TestSeederConstants::POST_ID . '/approval');
    $admin->request('DELETE', '/posts/' . TestSeederConstants::POST_ID . '/finish');
    $admin->request('DELETE', '/posts/' . TestSeederConstants::POST_ID . '/reservation');
  }
});
