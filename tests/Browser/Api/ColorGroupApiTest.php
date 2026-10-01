<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /color-groups (tag: color groups). Signed-in users manage color groups on appearances they own
// (personal guide) or, as staff, on any appearance. The seeded appearance belongs to the official guide.

$appearanceId = TestSeederConstants::APPEARANCE_ID;

function uniqueLabel(string $prefix):string {
  return substr($prefix . ' ' . substr(md5(uniqid('', true)), 0, 6), 0, 30);
}

function colorsJson(array $colors = [['label' => 'Contract Base', 'hex' => '#ff8800']]):string {
  return json_encode($colors);
}

function createGroup(ApiClient $admin, int $appearanceId, ?string $label = null):array {
  $r = $admin->post('/color-groups', ['appearanceId' => $appearanceId, 'label' => $label ?? uniqueLabel('Group'), 'colors' => colorsJson()]);
  expect($r['status'])->toBe(201);
  return $r['json'];
}

it('requires authentication for every color group endpoint', function () {
  $guest = ApiClient::guest();

  foreach ([['GET', '/color-groups/1'], ['POST', '/color-groups'], ['PUT', '/color-groups/1'], ['DELETE', '/color-groups/1']] as [$method, $path])
    expect($guest->request($method, $path)['status'])->toBe(401, "$method $path");
});

it('forbids regular users from official guide color groups', function () use ($appearanceId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $group = createGroup($admin, $appearanceId);

  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  expect($user->get('/color-groups/' . $group['id'])['status'])->toBe(403)
    ->and($user->request('PUT', '/color-groups/' . $group['id'], ['label' => 'Nope', 'colors' => colorsJson()])['status'])->toBe(403)
    ->and($user->request('DELETE', '/color-groups/' . $group['id'])['status'])->toBe(403)
    ->and($user->post('/color-groups', ['appearanceId' => $appearanceId, 'label' => 'Nope', 'colors' => colorsJson()])['status'])->toBe(403);

  ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', '/color-groups/' . $group['id']);
});

it('404s for missing color groups and appearances', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  expect($admin->get('/color-groups/987654')['status'])->toBe(404)
    ->and($admin->request('PUT', '/color-groups/987654', ['label' => 'x'])['status'])->toBe(404)
    ->and($admin->request('DELETE', '/color-groups/987654')['status'])->toBe(404)
    ->and($admin->post('/color-groups', ['appearanceId' => 987654, 'label' => 'Missing', 'colors' => colorsJson()])['status'])->toBe(404);
});

it('validates new color groups with 422 and field errors and saves nothing when rejected', function () use ($appearanceId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/color-groups');
  expect($r['status'])->toBe(422)->and($r['json'])->toHaveKeys(['message', 'errors'])->and($r['json']['errors'])->toHaveKey('appearanceId');

  $r = $admin->post('/color-groups', ['appearanceId' => $appearanceId]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('label');

  $label = uniqueLabel('Rejected');
  $base = ['appearanceId' => $appearanceId, 'label' => $label];
  expect($admin->post('/color-groups', $base)['json']['errors'])->toHaveKey('colors');
  expect($admin->post('/color-groups', $base + ['colors' => colorsJson([])])['json']['errors'])->toHaveKey('colors');
  expect($admin->post('/color-groups', $base + ['colors' => colorsJson([['label' => 'Bad Hex', 'hex' => 'nope']])])['json']['errors'])->toHaveKey('colors');
  expect($admin->post('/color-groups', $base + ['colors' => colorsJson([['label' => 'ab']])])['json']['errors'])->toHaveKey('colors');
  expect($admin->post('/color-groups', $base + ['colors' => colorsJson([['label' => 'Twin'], ['label' => 'Twin']])])['json']['errors'])->toHaveKey('colors');

  // None of the rejected attempts may have left a group behind: the same label is still free
  $group = createGroup($admin, $appearanceId, $label);
  $admin->request('DELETE', '/color-groups/' . $group['id']);
});

it('creates, reads, updates and deletes a color group', function () use ($appearanceId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $label = uniqueLabel('Lifecycle');

  $created = createGroup($admin, $appearanceId, $label);
  expect($created)->toHaveKey('id')->not->toHaveKey('status');
  $path = '/color-groups/' . $created['id'];

  $r = $admin->post('/color-groups', ['appearanceId' => $appearanceId, 'label' => $label, 'colors' => colorsJson()]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('label');

  $r = $admin->get($path);
  expect($r['status'])->toBe(200)
    ->and($r['json'])->toHaveKeys(['id', 'appearanceId', 'order', 'label', 'colors'])->not->toHaveKey('status')
    ->and($r['json']['label'])->toBe($label)
    ->and($r['json']['colors'])->toHaveCount(1)
    ->and($r['json']['colors'][0])->toHaveKeys(['id', 'order', 'label', 'hex'])
    ->and($r['json']['colors'][0]['hex'])->toBe('#FF8800');
  $colorId = $r['json']['colors'][0]['id'];

  $r = $admin->request('PUT', $path, [
    'label' => $label . ' 2',
    'colors' => colorsJson([['id' => $colorId, 'label' => 'Renamed Base', 'hex' => '#112233'], ['label' => 'Second Color']]),
  ]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKey('id');
  $r = $admin->get($path);
  expect($r['json']['label'])->toBe($label . ' 2')->and($r['json']['colors'])->toHaveCount(2);

  $r = $admin->request('PUT', $path, ['label' => $label . ' 2', 'colors' => colorsJson([['id' => 987654, 'label' => 'Ghost']])]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('colors');
  expect($admin->get($path)['json']['colors'])->toHaveCount(2);

  $r = $admin->request('DELETE', $path);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($admin->get($path)['status'])->toBe(404);
});

it('lets a user manage color groups on their own personal appearance', function () {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  $seeded = '/color-groups/' . TestSeederConstants::PERSONAL_COLOR_GROUP_ID;

  $r = $user->get($seeded);
  expect($r['status'])->toBe(200)
    ->and($r['json']['label'])->toBe('Personal Coat')
    ->and($r['json']['appearanceId'])->toBe(TestSeederConstants::PERSONAL_APPEARANCE_ID);

  $label = uniqueLabel('Mine');
  $r = $user->post('/color-groups', ['appearanceId' => TestSeederConstants::PERSONAL_APPEARANCE_ID, 'label' => $label, 'colors' => colorsJson()]);
  expect($r['status'])->toBe(201);
  $path = '/color-groups/' . $r['json']['id'];

  $r = $user->request('PUT', $path, ['label' => $label . ' 2', 'colors' => colorsJson([['label' => 'Personal Two', 'hex' => '#010203']])]);
  expect($r['status'])->toBe(200);
  expect($user->get($path)['json']['label'])->toBe($label . ' 2');

  expect($user->request('DELETE', $path)['status'])->toBe(204);
});

it('lets staff manage color groups on someone else\'s personal appearance', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  expect($admin->get('/color-groups/' . TestSeederConstants::PERSONAL_COLOR_GROUP_ID)['status'])->toBe(200);
});

it('does not let a user add color groups to the official guide through the personal guide rules', function () use ($appearanceId) {
  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);

  $r = $user->post('/color-groups', ['appearanceId' => $appearanceId, 'label' => uniqueLabel('Nope'), 'colors' => colorsJson()]);
  expect($r['status'])->toBe(403);
});
