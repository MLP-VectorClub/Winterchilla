<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Contract: /cg/colorgroup (tag: color groups). Signed-in users manage color groups on appearances they own
// (personal guide) or, as staff, on any appearance. The seeded appearance belongs to the official guide.

$appearanceId = TestSeederConstants::APPEARANCE_ID;

function uniqueLabel(string $prefix):string {
  return substr($prefix . ' ' . substr(md5(uniqid('', true)), 0, 6), 0, 30);
}

function colorsJson(array $colors = [['label' => 'Contract Base', 'hex' => '#ff8800']]):string {
  return json_encode($colors);
}

function createGroup(ApiClient $admin, int $appearanceId, ?string $label = null):array {
  $r = $admin->post('/cg/colorgroup', ['ponyid' => $appearanceId, 'label' => $label ?? uniqueLabel('Group'), 'Colors' => colorsJson()]);
  expect($r['status'])->toBe(201);
  return $r['json'];
}

it('requires authentication for every color group endpoint', function () {
  $guest = ApiClient::guest();

  foreach ([['GET', '/cg/colorgroup/1'], ['POST', '/cg/colorgroup'], ['PUT', '/cg/colorgroup/1'], ['DELETE', '/cg/colorgroup/1']] as [$method, $path])
    expect($guest->request($method, $path)['status'])->toBe(401, "$method $path");
});

it('forbids regular users from official guide color groups', function () use ($appearanceId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $group = createGroup($admin, $appearanceId);

  $user = ApiClient::loggedInAs(TestSeederConstants::USER_ID);
  expect($user->get('/cg/colorgroup/' . $group['id'])['status'])->toBe(403)
    ->and($user->request('PUT', '/cg/colorgroup/' . $group['id'], ['label' => 'Nope', 'Colors' => colorsJson()])['status'])->toBe(403)
    ->and($user->request('DELETE', '/cg/colorgroup/' . $group['id'])['status'])->toBe(403)
    ->and($user->post('/cg/colorgroup', ['ponyid' => $appearanceId, 'label' => 'Nope', 'Colors' => colorsJson()])['status'])->toBe(403);

  ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', '/cg/colorgroup/' . $group['id']);
});

it('404s for missing color groups and appearances', function () {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  expect($admin->get('/cg/colorgroup/987654')['status'])->toBe(404)
    ->and($admin->request('PUT', '/cg/colorgroup/987654', ['label' => 'x'])['status'])->toBe(404)
    ->and($admin->request('DELETE', '/cg/colorgroup/987654')['status'])->toBe(404)
    ->and($admin->post('/cg/colorgroup', ['ponyid' => 987654, 'label' => 'Missing', 'Colors' => colorsJson()])['status'])->toBe(404);
});

it('validates new color groups with 422 and field errors and saves nothing when rejected', function () use ($appearanceId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);

  $r = $admin->post('/cg/colorgroup');
  expect($r['status'])->toBe(422)->and($r['json'])->toHaveKeys(['message', 'errors'])->and($r['json']['errors'])->toHaveKey('ponyid');

  $r = $admin->post('/cg/colorgroup', ['ponyid' => $appearanceId]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('label');

  $label = uniqueLabel('Rejected');
  $base = ['ponyid' => $appearanceId, 'label' => $label];
  expect($admin->post('/cg/colorgroup', $base)['json']['errors'])->toHaveKey('Colors');
  expect($admin->post('/cg/colorgroup', $base + ['Colors' => colorsJson([])])['json']['errors'])->toHaveKey('Colors');
  expect($admin->post('/cg/colorgroup', $base + ['Colors' => colorsJson([['label' => 'Bad Hex', 'hex' => 'nope']])])['json']['errors'])->toHaveKey('Colors');
  expect($admin->post('/cg/colorgroup', $base + ['Colors' => colorsJson([['label' => 'ab']])])['json']['errors'])->toHaveKey('Colors');
  expect($admin->post('/cg/colorgroup', $base + ['Colors' => colorsJson([['label' => 'Twin'], ['label' => 'Twin']])])['json']['errors'])->toHaveKey('Colors');

  // None of the rejected attempts may have left a group behind: the same label is still free
  $group = createGroup($admin, $appearanceId, $label);
  $admin->request('DELETE', '/cg/colorgroup/' . $group['id']);
});

it('creates, reads, updates and deletes a color group', function () use ($appearanceId) {
  $admin = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $label = uniqueLabel('Lifecycle');

  $created = createGroup($admin, $appearanceId, $label);
  expect($created)->toHaveKeys(['id', 'cgs', 'notes'])->not->toHaveKey('status');
  $path = '/cg/colorgroup/' . $created['id'];

  $r = $admin->post('/cg/colorgroup', ['ponyid' => $appearanceId, 'label' => $label, 'Colors' => colorsJson()]);
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
    'Colors' => colorsJson([['id' => $colorId, 'label' => 'Renamed Base', 'hex' => '#112233'], ['label' => 'Second Color']]),
  ]);
  expect($r['status'])->toBe(200)->and($r['json'])->toHaveKeys(['id', 'cgs']);
  $r = $admin->get($path);
  expect($r['json']['label'])->toBe($label . ' 2')->and($r['json']['colors'])->toHaveCount(2);

  $r = $admin->request('PUT', $path, ['label' => $label . ' 2', 'Colors' => colorsJson([['id' => 987654, 'label' => 'Ghost']])]);
  expect($r['status'])->toBe(422)->and($r['json']['errors'])->toHaveKey('Colors');
  expect($admin->get($path)['json']['colors'])->toHaveCount(2);

  $r = $admin->request('DELETE', $path);
  expect($r['status'])->toBe(204)->and($r['body'])->toBe('');
  expect($admin->get($path)['status'])->toBe(404);
});
