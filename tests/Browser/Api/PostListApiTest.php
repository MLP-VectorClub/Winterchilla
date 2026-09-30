<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: GET /posts?showId&kind, the data twin of the HTML sections of an episode page (tag: posts). Public.

function listPosts(ApiClient $client, string $kind = 'request'):array {
  return $client->get('/posts', ['showId' => TestSeederConstants::SHOW_ID, 'kind' => $kind]);
}

it('lists the requests of a show as data', function () {
  $r = listPosts(ApiClient::guest());

  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('posts')->not->toHaveKey('status');
  $posts = array_column($r['json']['posts'], null, 'id');
  expect($posts)->toHaveKeys([TestSeederConstants::POST_ID, TestSeederConstants::RESERVED_POST_ID]);

  $open = $posts[TestSeederConstants::POST_ID];
  expect($open)->toHaveKeys(['id', 'kind', 'type', 'showId', 'label', 'previewUrl', 'fullsizeUrl', 'postedAt', 'postedBy', 'reservedBy', 'finishedAt', 'deviationId', 'approved', 'broken', 'overdue', 'canEdit'])
    ->and($open['kind'])->toBe('request')
    ->and($open['type'])->toBe('chr')
    ->and($open['postedBy'])->toBe(['id' => TestSeederConstants::USER_ID, 'name' => 'TestUser'])
    ->and($open['reservedBy'])->toBeNull()
    ->and($open['deviationId'])->toBeNull()
    ->and($open['canEdit'])->toBeFalse();

  $finished = $posts[TestSeederConstants::RESERVED_POST_ID];
  expect($finished['reservedBy']['name'])->toBe('TestAdmin')
    ->and($finished['deviationId'])->toBe('dfin001')
    ->and($finished['finishedAt'])->toBeString();
});

it('hides broken posts from everyone but staff', function () {
  $guestIds = array_column(listPosts(ApiClient::guest())['json']['posts'], 'id');
  $staff = array_column(listPosts(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID))['json']['posts'], null, 'id');

  expect($guestIds)->not->toContain(TestSeederConstants::BROKEN_POST_ID);
  expect($staff)->toHaveKey(TestSeederConstants::BROKEN_POST_ID)->and($staff[TestSeederConstants::BROKEN_POST_ID]['broken'])->toBeTrue();
});

it('tells the current user which posts they can edit', function () {
  $mine = array_column(listPosts(ApiClient::loggedInAs(TestSeederConstants::USER_ID))['json']['posts'], null, 'id');

  // An unreserved request can be edited by its requester, but not once somebody reserved it
  expect($mine[TestSeederConstants::POST_ID]['canEdit'])->toBeTrue()
    ->and($mine[TestSeederConstants::RESERVED_POST_ID]['canEdit'])->toBeFalse();
  $staff = array_column(listPosts(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID))['json']['posts'], null, 'id');
  expect($staff[TestSeederConstants::RESERVED_POST_ID]['canEdit'])->toBeTrue();
});

it('lists reservations as a separate kind', function () {
  $r = listPosts(ApiClient::guest(), 'reservation');

  expect($r['status'])->toBe(200)->and($r['json']['posts'])->toBeArray();
  foreach ($r['json']['posts'] as $post)
    expect($post['kind'])->toBe('reservation')->and($post)->not->toHaveKey('type');
});

it('validates the query and answers 404 for an unknown show', function () {
  $guest = ApiClient::guest();

  expect($guest->get('/posts', ['kind' => 'request'])['json']['errors'])->toHaveKey('showId');
  expect($guest->get('/posts', ['showId' => 1])['json']['errors'])->toHaveKey('kind');
  expect($guest->get('/posts', ['showId' => 1, 'kind' => 'nope'])['json']['errors'])->toHaveKey('kind');
  expect($guest->get('/posts', ['showId' => 987654, 'kind' => 'request'])['status'])->toBe(404);
});
