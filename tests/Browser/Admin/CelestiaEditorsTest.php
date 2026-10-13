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
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
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
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->click('button.edit-appearance-relations')
      ->click("button.list-group-item:has-text(\"Relation Pony B $suffix\")")
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
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->click('button.edit-show-relations')
      ->click('button.list-group-item:has-text("Friendship is Magic, Part 1")')
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
  $row = "tr:has-text(\"$name\")";

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSee($name)
      ->click("$row button[aria-label=\"Edit\"]")
      ->fill('#tag-name', $name . '-ed')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertSee($name . '-ed')
      // A tag that is in use asks once before it is deleted
      ->click("tr:has-text(\"$name-ed\") button[aria-label=\"Delete\"]")
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
  $row = "tr:has-text(\"$sourceName\")";

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSee($sourceName)
      ->click("$row button[aria-label=\"Make synonym\"]")
      ->fill('#synonym-target', (string)$target['id'])
      ->click('[data-testid="dialog-btn-make-synonym"]')
      ->assertSeeIn($row, 'synonym of')
      ->click("$row button[aria-label=\"Unlink synonym\"]")
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
  $row = "tr:has-text(\"$name\")";

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->click("$row button[aria-label=\"Refresh use count\"]")
      // The uses column of the tag table
      ->assertSeeIn("$row td.uses", '1');
  }
  finally {
    adminApi()->request('DELETE', '/tags/' . $tag['id'], ['sanityCheck' => 1]);
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('edits the tags of an appearance in place, with suggestions while typing', function () use ($base) {
  $suffix = substr(md5(uniqid('', true)), 0, 6);
  $name = "zzsuggest-$suffix";
  $typed = "zztyped-$suffix";
  $api = adminApi();
  $tag = $api->post('/tags', ['name' => $name, 'type' => 'app', 'addTo' => TestSeederConstants::APPEARANCE_ID])['json'];
  $other = $api->post('/appearances', ['guide' => 'pony', 'label' => "Tag Editor Pony $suffix"])['json']['id'];
  $input = '[data-testid="tag-editor"] input';

  try {
    // The tag is suggested with how often it is used, picking it adds it as a chip next to what is typed with Enter
    $page = visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $other)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->click('[data-testid="edit-tags-btn"]')
      ->assertPresent('[data-testid="tag-editor"]')
      ->type($input, substr($name, 0, 9))
      ->assertSeeIn('[role=listbox]', $name)
      ->click("[role=listbox] button:has-text(\"$name\")")
      ->type($input, $typed)
      ->keys($input, 'Enter')
      ->assertSeeIn('[data-testid="tag-editor"] .tag-editor', $name)
      ->assertSeeIn('[data-testid="tag-editor"] .tag-editor', $typed);

    // Plain text shows the same tags, saving keeps them
    $page->click('[data-testid="tag-editor-mode"]')
      ->assertValue('[data-testid="tag-editor"] textarea', "$name, $typed")
      ->click('[data-testid="tag-editor-save"]')
      ->assertMissing('[data-testid="tag-editor"]')
      ->assertSee($typed);

    $saved = $api->get("/appearances/$other/tags")['json']['tags'];
    expect($saved)->toContain($name)->and($saved)->toContain($typed);
  }
  finally {
    $api = adminApi();
    $api->request('DELETE', "/appearances/$other");
    $api->request('DELETE', '/tags/' . $tag['id'], ['sanityCheck' => 1]);
    foreach ($api->get('/tags', ['size' => 50])['json']['tags'] ?? [] as $t) {
      if ($t['name'] === $typed)
        $api->request('DELETE', '/tags/' . $t['id'], ['sanityCheck' => 1]);
    }
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('finds the synonym target of a tag by its name', function () use ($base) {
  $suffix = substr(md5(uniqid('', true)), 0, 6);
  $api = adminApi();
  $source = $api->post('/tags', ['name' => "zzsyn-from-$suffix", 'type' => 'app'])['json'];
  $target = $api->post('/tags', ['name' => "zzsyn-to-$suffix", 'type' => 'app'])['json'];
  $row = "tr:has-text(\"zzsyn-from-$suffix\")";

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/tags')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->click("$row button[aria-label=\"Make synonym\"]")
      ->type('#synonym-target', "zzsyn-to-$suffix")
      ->click("[role=listbox] button:has-text(\"zzsyn-to-$suffix\")")
      ->click('[data-testid="dialog-btn-make-synonym"]')
      ->assertSeeIn($row, 'synonym of');
  }
  finally {
    $api = adminApi();
    $api->request('DELETE', '/tags/' . $source['id']);
    $api->request('DELETE', '/tags/' . $target['id']);
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('selectively wipes the notes of an appearance', function () use ($base) {
  $api = adminApi();
  $suffix = substr(md5(uniqid('', true)), 0, 5);
  $id = $api->post('/appearances', ['guide' => 'pony', 'label' => "Wipe Pony $suffix", 'notes' => 'Notes to wipe'])['json']['id'];

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $id)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSee('Notes to wipe')
      ->click('button:text-is("Edit metadata")')
      ->click('button.selective-wipe')
      ->click('#wipe-' . $id . '-wipeNotes')
      ->click('[data-testid="dialog-btn-wipe"]')
      ->click('[data-testid="dialog-btn-confirm"]')
      ->assertDontSee('Notes to wipe');
  }
  finally {
    adminApi()->request('DELETE', "/appearances/$id");
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('lists the shows that air soon in the sidebar', function () use ($base) {
  $r = adminApi()->post('/show', ['type' => 'movie', 'title' => 'Sidebar Upcoming Movie', 'airs' => gmdate('Y-m-d H:i', time() + 3 * 86400)]);
  expect(in_array($r['status'], [200, 201], true))->toBeTrue();
  $id = $r['json']['id'] ?? $r['json']['show']['id'] ?? null;

  try {
    visit($base . '/cg')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertSee('Happening soon')
      ->assertSee('Sidebar Upcoming Movie');
  }
  finally {
    if ($id !== null)
      adminApi()->request('DELETE', "/show/$id");
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('publishes the color guide as a file other tools can read', function () use ($base) {
  $ch = curl_init($base . '/dist/mlpvc-colorguide.json');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true]);
  $body = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  expect($status)->toBe(200);
  $json = json_decode((string)$body, true);
  expect($json)->toHaveKeys(['Appearances', 'Tags']);

  visit($base . '/cg')->assertSee('JSON Export');
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('re-sorts the full list from the sort menu', function () use ($base) {
  visit($base . '/cg/pony/full')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->click('button:has-text("by relevance")')
    ->click('.dropdown-item:has-text("alphabetically")')
    ->assertQueryStringHas('sort_by', 'label')
    ->assertSee('Twilight Sparkle');
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('shows the buttons of a row in one size, even when one of them asks for a small one', function () use ($base) {
  $oneSize = <<<'JS'
(() => {
  const sizes = [...document.querySelectorAll('[class*="ButtonCollection"]:not([class*="compact"]) > .btn')]
    .filter((b) => b.offsetWidth)
    .map((b) => getComputedStyle(b).fontSize + '/' + Math.round(b.getBoundingClientRect().height));
  return [...new Set(sizes)].join(',');
})()
JS;

  // The appearance page has a small Share button between regular ones, the tags page a small "New tag" button
  visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
    ->navigate($base . '/cg/pony/v/' . TestSeederConstants::APPEARANCE_ID)
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertScript($oneSize, '16px/38')
    ->navigate($base . '/cg/pony/tags')
    ->assertScript($oneSize, '16px/38')
    ->navigate($base . '/cg/pony')
    ->assertScript($oneSize, '16px/38');
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('lays the guide list and the show table out for a phone like the old site', function () use ($base) {
  // An entry of the guide list is stacked: its sprite box is above its name, not next to it
  $stacked = <<<'JS'
(() => {
  const box = document.querySelector('[class*="spriteBox"]');
  const name = document.querySelector('[class*="appearanceName"]');
  if (!box || !name) return 'missing';
  return name.getBoundingClientRect().top >= box.getBoundingClientRect().bottom - 1 ? 'stacked' : 'side by side';
})()
JS;
  // The episode id of the show table is a bold line of text on a phone, the big numbers are for the wide table
  $idSize = "getComputedStyle(document.querySelector('[class*=\"mobileIdentifier\"]')).fontSize";

  visit($base . '/cg/pony')
    ->resize(390, 844)
    ->navigate($base . '/cg/pony')
    ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
    ->assertScript($stacked, 'stacked')
    ->navigate($base . '/show')
    ->assertScript($idSize, '16px');

  visit($base . '/cg/pony')
    ->resize(1280, 900)
    ->navigate($base . '/cg/pony')
    ->assertScript($stacked, 'side by side');
})->skip($notCelestia, 'Celestia only')->group('celestia-only');

it('shows the sprite of an appearance in the search suggestions, and the color squares when there is none', function () use ($base) {
  // Luna does not index appearances while it runs for tests, so this uses the seeded one, which the search index knows
  $api = adminApi();
  $id = TestSeederConstants::APPEARANCE_ID;
  $sprite = dirname(__DIR__, 3) . '/public/img/sprite_template/body_female.png';
  $suggestion = '[role=option] [data-testid="ac-result-sprite"]';

  try {
    visit($base . '/cg/pony')
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->type('input[name="q"]', 'Twilight')
      ->assertSeeIn('[role=option]', 'Twilight Sparkle')
      ->assertMissing($suggestion);

    expect($api->upload("/appearances/$id/sprite", 'sprite', $sprite)['status'])->toBeIn([200, 201, 204]);

    visit($base . '/cg/pony')
      ->type('input[name="q"]', 'Twilight')
      ->assertSeeIn('[role=option]', 'Twilight Sparkle')
      ->assertPresent($suggestion);
  }
  finally {
    $api->request('DELETE', "/appearances/$id/sprite");
  }
})->skip($notCelestia, 'Celestia only')->group('celestia-only');
