<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

$base = TestSeederConstants::BASE_URL;
// A valid 300x300 PNG that is part of the site's sprite template generator
$sprite = dirname(__DIR__, 3) . '/public/img/sprite_template/body_female.png';

it('uploads and removes a sprite image on an appearance page', function () use ($base, $sprite) {
  $api = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID);
  $id = $api->post('/appearances', ['guide' => 'pony', 'label' => 'Sprite UI Pony ' . substr(md5(uniqid('', true)), 0, 5)])['json']['id'];

  try {
    $page = visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
      ->navigate($base . '/cg/pony/v/' . $id)
      ->assertNoJavaScriptErrors()
      ->assertPresent('.upload-wrap.nosprite')
      // The uploader is a hidden file input inside the sprite wrapper
      ->attach('[data-testid="sprite-wrap"] input[type="file"]', $sprite)
      ->assertMissing('.upload-wrap.nosprite');

    $page
      ->rightClick('[data-testid="sprite-wrap"]')
      ->click('a:text-is("Remove sprite image")')
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
    visit($base . '/test-login/' . TestSeederConstants::ADMIN_ID)
      ->navigate($base . '/cg/pony/v/' . $id)
      ->attach('[data-testid="sprite-wrap"] input[type="file"]', dirname(__DIR__, 3) . '/public/img/blank-pixel.png')
      ->assertSee('too small, please upload a larger image')
      ->assertDontSee('<br>');
  }
  finally {
    ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', "/appearances/$id");
  }
});
