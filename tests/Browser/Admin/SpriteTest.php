<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::baseUrl();
// A valid 300x300 PNG that is part of the site's sprite template generator
$sprite = dirname(__DIR__, 3) . '/public/img/sprite_template/body_female.png';
// Winterchilla opens the sprite's actions with a right click, Celestia has a "⋯" button for it
$openSpriteMenu = fn($page) => TestSeederConstants::external()
  ? $page->click('[aria-label="Sprite actions"]')
  : $page->rightClick('[data-testid="sprite-wrap"]');

it('uploads and removes a sprite image on an appearance page', function () use ($base, $sprite, $openSpriteMenu) {
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = $api->post('/appearances', ['guide' => 'pony', 'label' => 'Sprite UI Pony ' . substr(md5(uniqid('', true)), 0, 5)])['json']['id'];

  try {
    $page = visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $id)
      ->assertNoJavaScriptErrors()->assertScript(APP_CONSOLE_ERRORS_JS, '')
      ->assertPresent('.upload-wrap.nosprite')
      // The uploader is a hidden file input inside the sprite wrapper
      ->attach('[data-testid="sprite-wrap"] input[type="file"]', $sprite)
      ->assertMissing('.upload-wrap.nosprite');

    $openSpriteMenu($page)
      ->click('Remove sprite image')
      ->click('[data-testid="dialog-btn-confirm"]')
      ->assertPresent('.upload-wrap.nosprite');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', "/appearances/$id");
  }
});

it('shows the API\'s error when a sprite upload is rejected', function () use ($base) {
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = $api->post('/appearances', ['guide' => 'pony', 'label' => 'Sprite UI Pony ' . substr(md5(uniqid('', true)), 0, 5)])['json']['id'];

  try {
    // 1x1 image: below the minimum sprite size
    visit(TestSeederConstants::loginUrl(TestSeederConstants::ADMIN_ID))
      ->navigate($base . '/cg/pony/v/' . $id)
      ->attach('[data-testid="sprite-wrap"] input[type="file"]', dirname(__DIR__, 3) . '/public/img/blank-pixel.png')
      ->assertSee('too small, please upload a larger image')
      ->assertDontSee('<br>');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', "/appearances/$id");
  }
});
