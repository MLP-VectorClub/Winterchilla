<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: PUT/GET /appearances/{id}/cutie-marks, the success paths (validation is in AppearanceContractTest).

$svg = file_get_contents(dirname(__DIR__) . '/fixtures/cutiemark.svg');

function scratchPony(ApiClient $admin):int {
  $r = $admin->post('/appearances', ['guide' => 'pony', 'label' => substr('CM Pony ' . substr(md5(uniqid('', true)), 0, 8), 0, 70)]);
  expect($r['status'])->toBe(201);
  return $r['json']['id'];
}

it('adds, renames and removes cutie marks on an appearance', function () use ($svg) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = scratchPony($admin);
  $path = "/appearances/$id/cutie-marks";

  $r = $admin->request('PUT', $path, ['cutieMarks' => json_encode([['svgdata' => $svg, 'facing' => 'left', 'attribution' => 'none', 'rotation' => 0, 'label' => 'Scratch CM']])]);
  expect($r['status'])->toBe(200)->and($r['json'])->not->toHaveKey('status');

  $r = $admin->get($path);
  expect($r['status'])->toBe(200)->and($r['json']['cms'])->toHaveCount(1);
  $cm = $r['json']['cms'][0];
  expect($cm['label'])->toBe('Scratch CM')->and($cm['facing'])->toBe('left');

  // Update it (no new SVG data needed for an existing mark)
  $r = $admin->request('PUT', $path, ['cutieMarks' => json_encode([['id' => $cm['id'], 'facing' => 'right', 'attribution' => 'none', 'rotation' => 10, 'label' => 'Renamed CM']])]);
  expect($r['status'])->toBe(200);
  $cm = $admin->get($path)['json']['cms'][0];
  expect($cm['label'])->toBe('Renamed CM')->and($cm['facing'])->toBe('right');

  // Leaving it out of the list removes it
  expect($admin->request('PUT', $path, ['cutieMarks' => '[]'])['status'])->toBe(200);
  expect($admin->get($path)['json']['cms'])->toBe([]);

  $admin->request('DELETE', "/appearances/$id");
});

it('rejects a cutie mark with an invalid rotation or attribution', function () use ($svg) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = scratchPony($admin);
  $path = "/appearances/$id/cutie-marks";
  $base = ['svgdata' => $svg, 'facing' => 'left', 'attribution' => 'none', 'rotation' => 0];

  foreach ([['rotation' => 90], ['attribution' => 'nonsense'], ['facing' => 'sideways']] as $bad) {
    $r = $admin->request('PUT', $path, ['cutieMarks' => json_encode([$bad + $base])]);
    expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('cutiemarks');
  }
  expect($admin->get($path)['json']['cms'])->toBe([]);

  $admin->request('DELETE', "/appearances/$id");
});

it('allows at most two cutie marks per appearance', function () use ($svg) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = scratchPony($admin);
  $path = "/appearances/$id/cutie-marks";
  $mark = ['svgdata' => $svg, 'facing' => 'left', 'attribution' => 'none', 'rotation' => 0];

  $r = $admin->request('PUT', $path, ['cutieMarks' => json_encode([$mark, $mark, $mark])]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('cutiemarks');
  expect($admin->get($path)['json']['cms'])->toBe([]);

  expect($admin->request('PUT', $path, ['cutieMarks' => json_encode([$mark, $mark])])['status'])->toBe(200);
  expect($admin->request('PUT', $path, ['cutieMarks' => '[]'])['status'])->toBe(200);
});

it('treats a cutie mark without a facing as symmetrical (null)', function () use ($svg) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = scratchPony($admin);
  $path = "/appearances/$id/cutie-marks";

  $r = $admin->request('PUT', $path, ['cutieMarks' => json_encode([['svgdata' => $svg, 'attribution' => 'none', 'rotation' => 0]])]);
  expect($r['status'])->toBe(200);
  $cms = $admin->get($path)['json']['cms'];
  expect($cms)->toHaveCount(1)->and($cms[0])->toHaveKey('facing')->and($cms[0]['facing'])->toBeNull();

  expect($admin->request('PUT', $path, ['cutieMarks' => '[]'])['status'])->toBe(200);
});
