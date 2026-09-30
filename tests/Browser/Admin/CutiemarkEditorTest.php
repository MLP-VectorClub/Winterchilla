<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;

it('renames a cutie mark in the cutie mark editor', function () use ($base) {
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = $api->post('/cg/appearance', ['guide' => 'pony', 'label' => 'CM Editor Pony ' . substr(md5(uniqid('', true)), 0, 5)])['json']['id'];
  $api->request('PUT', "/cg/appearance/$id/cutiemarks", ['CMData' => json_encode([[
    'svgdata' => file_get_contents(dirname(__DIR__) . '/fixtures/cutiemark.svg'), 'facing' => 'left', 'attribution' => 'none', 'rotation' => 0, 'label' => 'Before Editing',
  ]])]);

  try {
    visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
      ->navigate($base . '/cg/pony/v/' . $id)
      ->assertNoJavaScriptErrors()
      ->assertSee('Before Editing')
      ->click('[data-testid="edit-appearance-btn"]')
      ->click('.cg-cm-editor')
      ->assertValue('.custom-label', 'Before Editing')
      ->fill('.custom-label', 'After Editing')
      ->click('[data-testid="dialog-btn-save"]')
      ->assertSee('After Editing')
      ->assertDontSee('Before Editing');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', "/cg/appearance/$id");
  }
});
