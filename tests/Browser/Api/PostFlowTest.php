<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\ClubGallery;
use Tests\Browser\Helpers\TestSeederConstants;

// The success paths of the post endpoints that PostApiTest can't reach on its own: creating, finishing, approving,
// unbreaking and changing the image of posts. Image hosts and DeviantArt are replaced by the test server itself
// (a local image URL), Redis-cached deviations and the club gallery marker files (see ClubGallery).

$image = 'http://fav.me/dfin005';
$otherImage = 'http://fav.me/dfin006';
$postId = TestSeederConstants::POST_ID;

it('creates a request and a reservation from an image URL', function () use ($image) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/posts', ['kind' => 'request', 'showId' => TestSeederConstants::SHOW_ID, 'label' => 'A new request', 'type' => 'chr', 'imageUrl' => $image]);
  expect($r['status'])->toBe(201)->and($r['json'])->toHaveKeys(['id', 'idString', 'kind'])->and($r['json']['kind'])->toBe('request')->and($r['json']['id'])->toBeInt()->and($r['json']['idString'])->toBe('post-' . $r['json']['id']);
  $requestId = $r['json']['id'];

  $r = $admin->post('/posts', ['kind' => 'reservation', 'showId' => TestSeederConstants::SHOW_ID, 'label' => 'A new reservation', 'imageUrl' => $image]);
  expect($r['status'])->toBe(201)->and($r['json']['kind'])->toBe('reservation');

  // Clean up the request through the API
  expect($admin->request('DELETE', '/posts/requests/' . $requestId)['status'])->toBe(204);
});

it('validates new posts with 422 and field errors', function () use ($image) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/posts', ['kind' => 'nonsense']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('kind');

  $r = $admin->post('/posts', ['kind' => 'request', 'showId' => 987654, 'label' => 'A new request', 'type' => 'chr', 'imageUrl' => $image]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('showId');
});

it('checks an image URL and returns its preview', function () use ($image) {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->post('/posts/check-image', ['imageUrl' => $image]);

  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['preview', 'title'])->not->toHaveKey('status');
});

it('changes the image of a post', function () use ($otherImage, $postId) {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  $r = $user->request('PUT', "/posts/$postId/image", ['imageUrl' => $otherImage]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('preview');

  // The seeded reserved request can't get a new image once somebody reserved it
  expect($user->request('PUT', '/posts/' . TestSeederConstants::RESERVED_POST_ID . '/image', ['imageUrl' => $otherImage])['status'])->toBe(409);

  expect(ApiClient::guest()->request('PUT', "/posts/$postId/image", ['imageUrl' => $otherImage])['status'])->toBe(401);
});

it('unbreaks a post whose images are available again', function () {
  $path = '/posts/' . TestSeederConstants::BROKEN_POST_ID . '/unbreak';

  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->post($path)['status'])->toBe(403);

  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->post($path);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('li')->not->toHaveKey('status');
});

it('finishes, approves and unfinishes a reserved request', function () use ($postId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $reservation = "/posts/$postId/reservation";
  $finish = "/posts/$postId/finish";
  $approval = "/posts/$postId/approval";

  expect($admin->post($reservation)['status'])->toBe(200);

  // A deviation that is already the finished version of another post can't be used again
  $r = $admin->request('PUT', $finish, ['deviation' => 'http://fav.me/dfin001']);
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKey('message');

  // One by somebody else needs confirmation, and can then be forced
  $r = $admin->request('PUT', $finish, ['deviation' => 'http://fav.me/dfin004']);
  expect($r['status'])->toBe(409)->and($r['json'])->toMatchArray(['retry' => true]);

  $r = $admin->request('PUT', $finish, ['deviation' => 'http://fav.me/dfin002']);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['message', 'approved', 'notified'])
    ->and($r['json']['approved'])->toBeFalse()
    ->and($r['json']['notified']['name'])->toBe('TestUser');

  // Not in the club gallery yet
  expect($admin->post($approval)['status'])->toBe(409);

  ClubGallery::accept('dfin002');
  try {
    $r = $admin->post($approval);
    expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['message', 'li']);

    // Approved posts are locked, and can't be unlocked while they are in the gallery (that takes a developer)
    expect($admin->request('DELETE', $approval)['status'])->toBe(409);
    expect($admin->request('DELETE', $finish)['status'])->toBe(409);
  }
  finally {
    ClubGallery::reject('dfin002');
  }
  expect($admin->request('DELETE', $approval)['status'])->toBe(204);

  expect($admin->request('DELETE', $finish)['status'])->toBe(204);
  expect($admin->request('DELETE', $reservation)['status'])->toBe(200);
});
