<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// Celestia's versions of the editor flows whose Winterchilla originals drive Winterchilla's own DOM (right-click menus, split selects,
// one cutie mark editor inside the edit dialog, ...) and are tagged winterchilla-only. They check the same behavior through Celestia's
// dialogs and only run against another implementation (UI_BASE_URL, see TestSeederConstants::external()).

$base = TestSeederConstants::baseUrl();
$notCelestia = fn() => !TestSeederConstants::external();

function adminApi():ApiClient {
  return ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
}

it('renames a cutie mark in the cutie marks dialog', function () use ($base) {
  $api = adminApi();
  $id = $api->post('/appearances', ['guide' => 'pony', 'label' => 'CM Editor Pony ' . substr(md5(uniqid('', true)), 0, 5)])['json']['id'];
  $r = $api->json('PUT', "/appearances/$id/cutie-marks", ['cutieMarks' => [[
    'svgdata' => file_get_contents(dirname(__DIR__) . '/fixtures/cutiemark.svg'), 'facing' => 'left', 'attribution' => 'none', 'rotation' => 0, 'label' => 'Before Editing',
  ]]]);
  expect($r['status'])->toBe(200);

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $id)
      ->assertNoJavaScriptErrors()
      ->assertSee('Before Editing')
      ->click('button:text-is("Cutie marks")')
      ->fill('input[id$="-label"]', 'After Editing')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertSee('After Editing')
      ->assertDontSee('Before Editing');
  }
  finally {
    adminApi()->request('DELETE', "/appearances/$id");
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('links two appearances to each other in the related appearances dialog', function () use ($base) {
  $suffix = substr(md5(uniqid('', true)), 0, 5);
  $api = adminApi();
  $a = $api->post('/appearances', ['guide' => 'pony', 'label' => "Relation Pony A $suffix"])['json']['id'];
  $b = $api->post('/appearances', ['guide' => 'pony', 'label' => "Relation Pony B $suffix"])['json']['id'];

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $a)
      ->assertNoJavaScriptErrors()
      ->click('button:text-is("Related")')
      ->click(".list-group-item button:has-text(\"Relation Pony B $suffix\")")
      ->click('[data-testid="dialog-btn-save"]')
      ->assertSee("Relation Pony B $suffix");

    $r = adminApi()->get("/appearances/$a/relations");
    expect(array_map('intval', array_column($r['json']['linked'], 'id')))->toContain($b);
  }
  finally {
    $api = adminApi();
    $api->request('DELETE', "/appearances/$a");
    $api->request('DELETE', "/appearances/$b");
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('links a show to an appearance in the shows dialog and finds the appearance on the show page', function () use ($base) {
  $id = TestSeederConstants::APPEARANCE_ID;

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $id)
      ->assertNoJavaScriptErrors()
      ->click('button:text-is("Shows")')
      ->click('.list-group-item button:has-text("Friendship is Magic, Part 1")')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertSee('Friendship is Magic, Part 1')
      ->navigate($base . '/episode/' . TestSeederConstants::SHOW_ID)
      ->assertSee('Twilight Sparkle');
  }
  finally {
    adminApi()->request('PUT', "/appearances/$id/shows", ['ids' => '']);
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('edits and deletes a tag from the tag list', function () use ($base) {
  $name = 'ui-tag-' . substr(md5(uniqid('', true)), 0, 6);
  $tag = adminApi()->post('/tags', ['name' => $name, 'type' => 'app', 'addTo' => TestSeederConstants::APPEARANCE_ID])['json'];
  $row = "li:has-text(\"$name\")";

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()
      ->assertSee($name)
      ->click("$row button:text-is(\"Edit\")")
      ->fill('#tag-name', $name . '-ed')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertSee($name . '-ed')
      // A tag that is in use asks once before it is deleted
      ->click("li:has-text(\"$name-ed\") button:text-is(\"Delete\")")
      ->click('[data-testid="dialog-btn-confirm"]')
      ->assertDontSee($name . '-ed');
  }
  finally {
    adminApi()->request('DELETE', '/tags/' . $tag['id'], ['sanityCheck' => 1]);
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('makes a tag a synonym of another and removes the synonym again', function () use ($base) {
  $suffix = substr(md5(uniqid('', true)), 0, 6);
  $sourceName = "syn-source-$suffix";
  $api = adminApi();
  $source = $api->post('/tags', ['name' => $sourceName, 'type' => 'app'])['json'];
  $target = $api->post('/tags', ['name' => "syn-target-$suffix", 'type' => 'app'])['json'];
  $row = "li:has-text(\"$sourceName\")";

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()
      ->assertSee($sourceName)
      ->click("$row button:text-is(\"Make synonym\")")
      ->fill('#synonym-target', (string)$target['id'])
      ->click('[data-testid="dialog-btn-make-synonym"]')
      ->assertSeeIn($row, 'synonym of')
      ->click("$row button:text-is(\"Unlink\")")
      ->click('[data-testid="dialog-btn-confirm"]')
      ->assertDontSeeIn($row, 'synonym of');
  }
  finally {
    $api = adminApi();
    $api->request('DELETE', '/tags/' . $source['id']);
    $api->request('DELETE', '/tags/' . $target['id']);
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('recounts the uses of a tag from the tag list', function () use ($base) {
  $name = 'refresh-tag-' . substr(md5(uniqid('', true)), 0, 6);
  $tag = adminApi()->post('/tags', ['name' => $name, 'type' => 'app', 'addTo' => TestSeederConstants::APPEARANCE_ID])['json'];
  $row = "li:has-text(\"$name\")";

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()
      ->click("$row button:text-is(\"Recount\")")
      ->assertSeeIn($row, '1 use');
  }
  finally {
    adminApi()->request('DELETE', '/tags/' . $tag['id'], ['sanityCheck' => 1]);
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');
