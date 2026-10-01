<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: sprite upload and removal on /appearances/{id}/sprite, and the public /appearances/{id}/sprite image.
// public/img/sprite_template/body_female.png is a valid sprite (a 300x300 PNG) and doubles as the upload fixture.

$sprite = dirname(__DIR__, 3) . '/public/img/sprite_template/body_female.png';

function scratchAppearance(ApiClient $admin):int {
  $r = $admin->post('/appearances', ['guide' => 'pony', 'label' => substr('Sprite Pony ' . substr(md5(uniqid('', true)), 0, 8), 0, 70)]);
  expect($r['status'])->toBe(201);
  return $r['json']['id'];
}

it('requires authentication and permission to upload or remove sprites', function () use ($sprite) {
  $path = '/appearances/' . TestSeederConstants::APPEARANCE_ID . '/sprite';

  expect(ApiClient::guest()->upload($path, 'sprite', $sprite)['status'])->toBe(401);
  expect(ApiClient::guest()->request('DELETE', $path)['status'])->toBe(401);

  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  expect($user->upload($path, 'sprite', $sprite)['status'])->toBe(403);
  expect($user->request('DELETE', $path)['status'])->toBe(403);
});

it('uploads a sprite, serves it publicly and removes it again', function () use ($sprite) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = scratchAppearance($admin);
  $path = "/appearances/$id/sprite";

  $r = $admin->upload($path, 'sprite', $sprite);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('path')->not->toHaveKey('status');

  // The public API serves the uploaded image
  $r = ApiClient::guest()->get("/appearances/$id/sprite");
  expect($r['status'])->toBe(200)->and($r['contentType'])->toStartWith('image/png');

  $r = $admin->request('DELETE', $path);
  // The body (the default sprite's path) is a Winterchilla detail; implementations without a default sprite answer 204
  expect($r['status'])->toBeIn([200, 204]);

  expect($admin->request('DELETE', $path)['status'])->toBe(404);
  // Without an uploaded sprite Winterchilla serves a default image; an implementation may answer 404 and let the front end show a placeholder
  $r = ApiClient::guest()->get("/appearances/$id/sprite");
  expect($r['status'])->toBeIn([200, 404]);
  if ($r['status'] === 200)
    expect($r['contentType'])->toStartWith('image/png');

  $admin->request('DELETE', "/appearances/$id");
});

it('rejects files that are not usable sprites with 422', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = scratchAppearance($admin);
  $path = "/appearances/$id/sprite";

  // Not an image at all
  $r = $admin->upload($path, 'sprite', __FILE__, mime: 'text/plain');
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('file');

  // A valid PNG of the wrong size (the 1x1 placeholder is smaller than the minimum sprite height)
  $r = $admin->upload($path, 'sprite', dirname(__DIR__, 3) . '/public/img/blank-pixel.png');
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('file');

  $admin->request('DELETE', "/appearances/$id");
});
