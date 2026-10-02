<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: appearance palette/image downloads, cutie mark downloads, tag changes, the staff PCG appearance list and
// the user lookup by DeviantArt UUID.

$appearance = TestSeederConstants::APPEARANCE_ID;
$privatePersonal = TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID;

it('downloads a palette as json or gpl', function () use ($appearance) {
  $guest = ApiClient::guest();

  $r = $guest->get("/appearances/$appearance/palette", ['format' => 'json']);
  expect($r['status'])->toBe(200);
  $swatches = json_decode($r['body'], true);
  expect($swatches)->toBeArray()->toHaveKey('Version');
  // appearance label => color group => color label => hex
  $maps = array_values(array_filter($swatches, fn($v) => is_array($v)));
  expect($maps)->toHaveCount(1);
  foreach ($maps[0] as $colors)
    foreach ($colors as $hex)
      expect($hex)->toMatch('/^#[0-9A-F]{6}$/i');

  $r = $guest->get("/appearances/$appearance/palette", ['format' => 'gpl']);
  expect($r['status'])->toBe(200)->and($r['body'])->toStartWith('GIMP Palette');

  expect($guest->get("/appearances/$appearance/palette", ['format' => 'png'])['status'])->toBe(422);
  expect($guest->get("/appearances/$appearance/palette")['status'])->toBe(422);
  expect($guest->get('/appearances/99999999/palette', ['format' => 'gpl'])['status'])->toBe(404);
});

it('keeps private appearances out of the exports', function () use ($privatePersonal) {
  $guest = ApiClient::guest();
  expect($guest->get("/appearances/$privatePersonal/palette", ['format' => 'json'])['status'])->toBe(403);
  expect($guest->get("/appearances/$privatePersonal/image", ['type' => 'preview', 'format' => 'svg'])['status'])->toBe(403);
});

it('renders appearance images', function () use ($appearance) {
  $guest = ApiClient::guest();

  $r = $guest->get("/appearances/$appearance/image", ['type' => 'palette', 'format' => 'png']);
  expect($r['status'])->toBe(200)->and($r['contentType'])->toStartWith('image/png')->and(substr($r['body'], 1, 3))->toBe('PNG');

  $r = $guest->get("/appearances/$appearance/image", ['type' => 'preview', 'format' => 'svg']);
  expect($r['status'])->toBe(200)->and($r['contentType'])->toStartWith('image/svg+xml')->and($r['body'])->toContain('<svg');

  foreach (['left', 'right'] as $facing) {
    $r = $guest->get("/appearances/$appearance/image", ['type' => 'facing', 'format' => 'svg', 'facing' => $facing]);
    expect($r['status'])->toBe(200)->and($r['body'])->toContain('<svg');
  }
  expect($guest->get("/appearances/$appearance/image", ['type' => 'facing', 'format' => 'svg', 'facing' => 'up'])['status'])->toBe(422);

  // Invalid or unsupported combinations
  expect($guest->get("/appearances/$appearance/image")['status'])->toBe(422);
  expect($guest->get("/appearances/$appearance/image", ['type' => 'nonsense', 'format' => 'png'])['status'])->toBe(422);
  expect($guest->get("/appearances/$appearance/image", ['type' => 'palette', 'format' => 'svg'])['status'])->toBe(422);
  expect($guest->get("/appearances/$appearance/image", ['type' => 'preview', 'format' => 'png'])['status'])->toBe(422);
  expect($guest->get('/appearances/99999999/image', ['type' => 'preview', 'format' => 'svg'])['status'])->toBe(404);
});

it('downloads cutie marks, and the original only for staff', function () use ($appearance) {
  $cm = TestSeederConstants::CUTIEMARK_ID;
  $guest = ApiClient::guest();
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $path = fn(int $app) => "/appearances/$app/cutie-marks/$cm/download";

  $r = $guest->get($path($appearance));
  expect($r['status'])->toBe(200)->and($r['body'])->toContain('<svg');

  expect($guest->get($path($appearance), ['source' => 1])['status'])->toBeIn([401, 403]);
  expect($user->get($path($appearance), ['source' => 1])['status'])->toBe(403);
  $r = $admin->get($path($appearance), ['source' => 1]);
  expect($r['status'])->toBe(200)->and($r['body'])->toContain('<svg');

  expect($guest->get("/appearances/$appearance/cutie-marks/99999999/download")['status'])->toBe(404);
  expect($guest->get('/appearances/' . TestSeederConstants::DELETABLE_APPEARANCE_ID . "/cutie-marks/$cm/download")['status'])->toBe(404);
});

it('lists the tag changes of an appearance for staff', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $appearance = TestSeederConstants::APPEARANCE_ID;
  $path = "/appearances/$appearance/tag-changes";

  expect(ApiClient::guest()->get($path)['status'])->toBeIn([401, 403]);
  expect($user->get($path)['status'])->toBe(403);

  $name = 'chg' . substr(md5(uniqid('', true)), 0, 8);
  expect($admin->post('/tags', ['name' => $name, 'type' => 'app', 'addTo' => $appearance])['status'])->toBe(201);

  $r = $admin->get($path);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['changes', 'pagination']);
  $latest = $r['json']['changes'][0];
  expect($latest)->toHaveKeys(['id', 'tagId', 'tagName', 'added', 'user', 'createdAt'])
    ->and($latest['tagName'])->toBe($name)
    ->and($latest['added'])->toBeTrue()
    ->and($latest['user']['id'])->toBe(TestSeederConstants::ADMIN_ID);
  expect($r['json']['pagination'])->toHaveKeys(['currentPage', 'totalPages', 'totalItems', 'itemsPerPage']);

  expect($admin->get($path, ['size' => 0])['status'])->toBe(422);
  expect($admin->get('/appearances/99999999/tag-changes')['status'])->toBe(404);
  // Personal guide appearances have no history
  expect($admin->get('/appearances/' . TestSeederConstants::PERSONAL_APPEARANCE_ID . '/tag-changes')['status'])->toBe(404);
});

it('lists all personal guide appearances for staff', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  expect(ApiClient::guest()->get('/admin/pcg-appearances')['status'])->toBeIn([401, 403]);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->get('/admin/pcg-appearances')['status'])->toBe(403);

  $r = $admin->get('/admin/pcg-appearances', ['size' => 50]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['appearances', 'pagination']);
  $ids = array_column($r['json']['appearances'], 'id');
  expect($ids)->toContain(TestSeederConstants::PERSONAL_APPEARANCE_ID)->toContain(TestSeederConstants::PRIVATE_PERSONAL_APPEARANCE_ID);
  foreach ($r['json']['appearances'] as $a) {
    expect($a)->toHaveKeys(['id', 'label', 'guide', 'ownerId', 'previewData', 'private', 'createdAt']);
    expect($a['ownerId'])->not->toBeNull();
  }
  expect($r['json']['pagination']['totalItems'])->toBeGreaterThanOrEqual(2);

  $r = $admin->get('/admin/pcg-appearances', ['size' => 1, 'page' => 2]);
  expect($r['json']['appearances'])->toHaveCount(1)->and($r['json']['pagination']['currentPage'])->toBe(2);
  expect($admin->get('/admin/pcg-appearances', ['page' => 0])['status'])->toBe(422);
});

it('looks a user up by DeviantArt UUID for developers only', function () {
  $uuid = TestSeederConstants::USER_DA_ID;
  $path = "/users/da-uuid/$uuid";

  expect(ApiClient::guest()->get($path)['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get($path)['status'])->toBe(403);

  $developer = ApiClient::loggedInAs(TestSeederConstants::DEVELOPER_ID);
  $r = $developer->get($path);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['id', 'name', 'role'])->and($r['json']['id'])->toBe(TestSeederConstants::USER_ID);
  expect($developer->get('/users/da-uuid/' . TestSeederConstants::DEVELOPER_DA_ID)['json']['name'])->toBe('TestDeveloper');
  expect($developer->get('/users/da-uuid/00000000-0000-4000-8000-000000000000')['status'])->toBe(404);
});
