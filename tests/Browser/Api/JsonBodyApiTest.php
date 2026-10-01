<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// The write API takes `application/json` bodies (what the OpenAPI document describes and what a fetch()-based front end sends)
// as well as the form-encoded bodies Winterchilla's own jQuery client sends. The same inputs have to work either way.

it('accepts plain JSON values', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $name = 'json-tag-' . substr(md5(uniqid('', true)), 0, 8);

  $r = $admin->json('POST', '/tags', ['name' => $name, 'type' => 'app']);
  expect($r['status'])->toBe(201)->and($r['json']['name'])->toBe($name);

  expect($admin->json('PUT', '/tags/' . $r['json']['id'], ['name' => $name . '-2', 'type' => 'cat'])['status'])->toBe(200);
  expect($admin->request('DELETE', '/tags/' . $r['json']['id'])['status'])->toBe(204);
});

it('accepts nested JSON and numbers', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->json('POST', '/color-groups', [
    'appearanceId' => TestSeederConstants::APPEARANCE_ID,
    'label' => 'JSON Group ' . substr(md5(uniqid('', true)), 0, 6),
    'colors' => [['label' => 'JSON Base', 'hex' => '#112233']],
  ]);
  expect($r['status'])->toBe(201);

  try {
    $group = $admin->get('/color-groups/' . $r['json']['id'])['json'];
    expect($group['colors'][0]['hex'])->toBe('#112233');
  }
  finally {
    $admin->request('DELETE', '/color-groups/' . $r['json']['id']);
  }
});

it('accepts lists of numbers and booleans', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $show = '/show/' . TestSeederConstants::SHOW_ID . '/appearances';

  try {
    expect($admin->json('PUT', $show, ['ids' => [TestSeederConstants::APPEARANCE_ID]])['status'])->toBeIn([200, 204]);
    expect($admin->get($show)['json']['linkedIds'])->toBe([TestSeederConstants::APPEARANCE_ID]);
  }
  finally {
    $admin->json('PUT', $show, ['ids' => []]);
  }
  expect($admin->get($show)['json']['linkedIds'])->toBe([]);

  $created = $admin->json('POST', '/appearances', ['guide' => 'pony', 'label' => 'JSON Bool ' . substr(md5(uniqid('', true)), 0, 6)]);
  expect($created['status'])->toBe(201);
  $id = $created['json']['id'];
  try {
    expect($admin->json('DELETE', "/appearances/$id/contents", ['wipeNotes' => true, 'wipeCache' => false])['status'])->toBe(204);
  }
  finally {
    $admin->request('DELETE', "/appearances/$id");
  }
});

it('reports validation errors for JSON bodies in the same shape', function () {
  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->json('POST', '/tags', ['name' => 'x', 'type' => 'nonsense']);

  expect($r['status'])->toBe(422)->and($r['json'])->toHaveKeys(['message', 'errors']);
});
