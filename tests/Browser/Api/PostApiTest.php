<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /post endpoints (tag: posts). Requests and reservations on show pages. Endpoints that need to reach out
// to image hosts (creating posts, setting images, finishing with a deviation) aren't exercised here.

$postId = TestSeederConstants::POST_ID;

it('requires authentication for every write endpoint', function () use ($postId) {
  $guest = ApiClient::guest();

  foreach ([
    ['POST', '/post'], ['PUT', "/post/$postId"], ['GET', "/post/$postId"], ['POST', "/post/$postId/reservation"],
    ['DELETE', "/post/$postId/reservation"], ['POST', "/post/$postId/approval"], ['DELETE', "/post/$postId/approval"],
    ['PUT', "/post/$postId/finish"], ['DELETE', "/post/$postId/finish"], ['DELETE', "/post/request/$postId"],
    ['PUT', "/post/$postId/image"], ['POST', '/post/check-image'], ['POST', '/post/reservation'], ['GET', '/post/request/suggestion'],
  ] as [$method, $path]) {
    $r = $guest->request($method, $path);
    expect($r['status'])->toBe(401, "$method $path")->and($r['json'])->toHaveKey('message')->not->toHaveKey('status');
  }
});

it('requires club membership to reserve, approve or finish posts', function () use ($postId) {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  foreach ([['POST', "/post/$postId/reservation"], ['POST', "/post/$postId/approval"], ['PUT', "/post/$postId/finish"]] as [$method, $path])
    expect($user->request($method, $path)['status'])->toBe(403, "$method $path");
});

it('returns 404 for posts that do not exist', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  foreach ([['GET', '/post/987654'], ['PUT', '/post/987654'], ['POST', '/post/987654/reservation'], ['GET', '/post/987654/lazyload'], ['POST', '/post/987654/locate'], ['GET', '/post/987654/unbreak']] as [$method, $path])
    expect($admin->request($method, $path)['status'])->toBe(404, "$method $path");
});

it('does not let other users read or edit a request', function () use ($postId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  // Staff may; a second regular user would not, and the reserved request belongs to the admin as reserver only
  expect($admin->get("/post/$postId")['status'])->toBe(200);

  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  // Reserved requests can't be edited by their requester any more
  expect($user->request('PUT', '/post/' . TestSeederConstants::RESERVED_POST_ID, ['label' => 'Hijacked label'])['status'])->toBe(403);
});

it('lets the requester read and edit their request', function () use ($postId) {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  $r = $user->get("/post/$postId");
  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['label', 'type'])->not->toHaveKey('status')
    ->and($r['json']['label'])->toBe('Seeded Test Request');

  $r = $user->request('PUT', "/post/$postId", ['label' => 'Edited Test Request', 'type' => 'obj']);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($user->get("/post/$postId")['json'])->toMatchArray(['label' => 'Edited Test Request', 'type' => 'obj']);

  // Nothing to change is still a success
  expect($user->request('PUT', "/post/$postId", ['label' => 'Edited Test Request', 'type' => 'obj'])['status'])->toBe(204);

  $user->request('PUT', "/post/$postId", ['label' => 'Seeded Test Request', 'type' => 'chr']);
});

it('validates post edits with 422 and field errors', function () use ($postId) {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  $r = $user->request('PUT', "/post/$postId", ['label' => 'ab']);
  expect($r['status'])->toBe(422)->and($r['json'])->toHaveKeys(['message', 'errors'])->and($r['json']['errors'])->toHaveKey('label');

  $r = $user->request('PUT', "/post/$postId", ['label' => 'Fine label', 'type' => 'nonsense']);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('type');
});

it('locates posts for the share/link redirect flow', function () use ($postId) {
  $guest = ApiClient::guest();

  $r = $guest->post("/post/$postId/locate", ['show_id' => TestSeederConstants::SHOW_ID]);
  expect($r['status'])->toBe(200)->and($r['json'])->toBe(['refresh' => 'request']);

  $r = $guest->post("/post/$postId/locate", ['show_id' => TestSeederConstants::MOVIE_ID]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('castle')->and($r['json']['castle'])->toHaveKeys(['name', 'url']);
});

it('returns the rendered image of a finished post and 409s for unfinished ones', function () use ($postId) {
  $r = ApiClient::guest()->get('/post/' . TestSeederConstants::RESERVED_POST_ID . '/lazyload');
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('html')->not->toHaveKey('status')
    ->and($r['json']['html'])->toContain('dfin001');

  $r = ApiClient::guest()->get("/post/$postId/lazyload");
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKey('message');
});

it('reloads the list item of a finished post', function () {
  // Posts without a deviation get their image URLs checked over the network (and marked broken when they 404),
  // so only the finished seed post is safe to reload here
  $r = ApiClient::guest()->get('/post/' . TestSeederConstants::RESERVED_POST_ID . '/reload');

  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['li', 'section'])->not->toHaveKey('status');
});

it('suggests an unreserved request to signed-in users', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/post/request/suggestion');

  expect($r['status'])->toBeIn([200, 404]);
  if ($r['status'] === 200)
    expect($r['json'])->toHaveKey('suggestion');
  else
    expect($r['json'])->toHaveKey('message');
});

it('rejects an unusable image URL when checking images', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::USER_ID)->post('/post/check-image');

  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('image_url');
});

it('reserves, re-reserves and unreserves a request', function () use ($postId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $path = "/post/$postId/reservation";

  $r = $admin->post($path);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('li')->not->toHaveKey('status');

  $r = $admin->post($path);
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKeys(['message', 'li']);

  // Approving or finishing something that isn't finished yet conflicts with the post's state
  expect($admin->post("/post/$postId/approval")['status'])->toBe(409);
  expect($admin->request('DELETE', "/post/$postId/approval")['status'])->toBe(409);

  $r = $admin->request('DELETE', $path);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('li');
  // Already unreserved is a no-op success
  expect($admin->request('DELETE', $path)['status'])->toBe(200);
});

it('conflicts when finishing an unreserved post', function () use ($postId) {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('PUT', "/post/$postId/finish", ['deviation' => 'http://fav.me/dabcdef1']);

  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKey('message');
});

it('deletes a request as its owner but not once it has been reserved', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  $r = $user->request('DELETE', '/post/request/' . TestSeederConstants::RESERVED_POST_ID);
  expect($r['status'])->toBe(409)->and($r['json'])->toHaveKey('message');

  $r = $user->request('DELETE', '/post/request/' . TestSeederConstants::DELETABLE_POST_ID);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($user->request('DELETE', '/post/request/' . TestSeederConstants::DELETABLE_POST_ID)['status'])->toBe(404);
});

it('does not let users delete other people\'s requests', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  // Staff may delete anyone's request, so use the regular user against the admin's reservation instead
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  expect($user->request('DELETE', '/post/987654')['status'])->toBeIn([404, 405]);
  expect($admin->request('GET', '/post/request/987654')['status'])->toBeIn([404, 405]);
});
