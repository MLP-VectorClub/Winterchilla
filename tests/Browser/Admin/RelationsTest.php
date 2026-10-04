<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();

it('links two appearances to each other in the relations editor', function () use ($base) {
  $suffix = substr(md5(uniqid('', true)), 0, 5);
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $a = $api->post('/appearances', ['guide' => 'pony', 'label' => "Relation Pony A $suffix"])['json']['id'];
  $b = $api->post('/appearances', ['guide' => 'pony', 'label' => "Relation Pony B $suffix"])['json']['id'];

  try {
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $a)
      ->assertNoJavaScriptErrors()
      ->click('section.related .edit-appearance-relations')
      ->select('#guide-relation-editor .split-select:last-child select', (string)$b)
      ->click('#guide-relation-editor button[title="Link selected"]')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertSee("Relation Pony B $suffix");

    // The link is stored (one way unless the editor said mutual)
    $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->get("/appearances/$a/relations");
    expect(array_map('intval', array_column($r['json']['linked'], 'id')))->toContain($b);
  }
  finally {
    $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
    $api->request('DELETE', "/appearances/$a");
    $api->request('DELETE', "/appearances/$b");
  }
})->group('winterchilla-only');
