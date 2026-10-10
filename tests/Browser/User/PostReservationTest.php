<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('reserves a request and cancels the reservation from the episode page', function () use ($base) {
  $post = '#post-' . TestSeederConstants::POST_ID;

  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertSee('Seeded Test Request')
    ->click("$post .reserve-request")
    // The list item is replaced with the one the API returned, which now offers to cancel the reservation
    ->assertPresent("$post .cancel")
    ->click("$post .cancel")
    ->click('[data-testid="dialog-btn-confirm"]')
    ->assertPresent("$post .reserve-request")
    ->assertMissing("$post .cancel");
});

it('lets another member take over a reservation that has gone overdue', function () use ($base) {
  $postId = TestSeederConstants::POST_ID;
  $post = "#post-$postId";
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $developer = ApiClient::loggedInAs(TestSeederConstants::DEVELOPER_ID);
  $taker = TestSeederConstants::FRESH_USER_ID;

  try {
    // The admin reserved it four weeks ago and never finished it, the taker is a member without any say over it
    expect($admin->post("/posts/$postId/reservation")['status'])->toBe(200);
    expect($developer->json('PUT', "/posts/$postId", ['reservedAt' => date('c', strtotime('-4 weeks'))])['status'])->toBeIn([200, 204]);
    expect($admin->json('PUT', "/users/$taker/role", ['value' => 'member'])['status'])->toBeIn([200, 204]);

    visit(TestSeederConstants::loginUrl($taker))
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      // The reserver is not shown, the post looks free
      ->assertMissing("$post .reserver")
      ->assertPresent("$post .reserve-request")
      ->click("$post .reserve-request")
      // Now it is theirs: they can cancel it and their name is on it
      ->assertPresent("$post .cancel")
      ->assertSeeIn("$post .reserver", 'FreshUser');

    $reserved = array_column($admin->get('/posts', ['showId' => TestSeederConstants::SHOW_ID, 'kind' => 'request'])['json']['posts'], null, 'id')[$postId];
    expect($reserved['reservedBy']['id'])->toBe($taker);
  }
  finally {
    $admin->request('DELETE', "/posts/$postId/reservation");
    $admin->json('PUT', "/users/$taker/role", ['value' => 'user']);
  }
});

it('still shows staff who an overdue reservation belongs to', function () use ($base) {
  $postId = TestSeederConstants::POST_ID;
  $post = "#post-$postId";
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $developer = ApiClient::loggedInAs(TestSeederConstants::DEVELOPER_ID);

  try {
    expect($developer->post("/posts/$postId/reservation")['status'])->toBe(200);
    expect($developer->json('PUT', "/posts/$postId", ['reservedAt' => date('c', strtotime('-4 weeks'))])['status'])->toBeIn([200, 204]);

    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSeeIn("$post .reserver", 'TestDeveloper')
      ->assertSeeIn($post, 'by TestDeveloper')
      ->assertMissing("$post .reserve-request");
  }
  finally {
    $admin->request('DELETE', "/posts/$postId/reservation");
  }
});

// The old site only showed the note to the reserver and staff, Celestia shows it to every member who could take the reservation over
it('tells members that an overdue reservation can be contested', function () use ($base) {
  $postId = TestSeederConstants::POST_ID;
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $developer = ApiClient::loggedInAs(TestSeederConstants::DEVELOPER_ID);
  $member = TestSeederConstants::FRESH_USER_ID;

  try {
    expect($admin->post("/posts/$postId/reservation")['status'])->toBe(200);
    expect($developer->json('PUT', "/posts/$postId", ['reservedAt' => date('c', strtotime('-4 weeks'))])['status'])->toBeIn([200, 204]);
    expect($admin->json('PUT', "/users/$member/role", ['value' => 'member'])['status'])->toBeIn([200, 204]);

    visit(TestSeederConstants::loginUrl($member))
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSeeIn("#post-$postId", 'Can be contested');
  }
  finally {
    $admin->request('DELETE', "/posts/$postId/reservation");
    $admin->json('PUT', "/users/$member/role", ['value' => 'user']);
  }
})->skip(fn() => !TestSeederConstants::external(), 'Celestia only')->group('celestia-only');
