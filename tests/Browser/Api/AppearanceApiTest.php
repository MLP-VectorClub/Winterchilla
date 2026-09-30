<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// DELETE /cg/appearance/{id} (staff only). Not a status-code contract yet, see the migration progress in CLAUDE.md.

it('removes the cutie mark files along with a deleted appearance', function () {
  $fs = dirname(__DIR__, 3) . '/fs/';
  $id = TestSeederConstants::DELETABLE_CUTIEMARK_ID;
  $files = ["{$fs}cm_source/$id.svg", "{$fs}cm_tokenized/$id.svg", "{$fs}cg_render/cutiemark/$id.svg"];
  foreach ($files as $file)
    expect(file_exists($file))->toBeTrue("$file should be seeded");

  $appearance_path = '/cg/appearance/' . TestSeederConstants::DELETABLE_APPEARANCE_ID;
  // Rejection looks different per caller while this controller isn't migrated to HTTP statuses (legacy `{status: false}`
  // for guests, 403 for users), so only assert that it is rejected
  foreach ([ApiClient::guest(), ApiClient::loggedInAs(TestSeederConstants::USER_ID)] as $client) {
    $r = $client->request('DELETE', $appearance_path);
    expect($r['status'] >= 400 || ($r['json']['status'] ?? null) === false)->toBeTrue();
  }
  foreach ($files as $file)
    expect(file_exists($file))->toBeTrue("$file should survive a rejected delete");

  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', $appearance_path);
  expect($r['json']['status'])->toBeTrue();

  foreach ($files as $file)
    expect(file_exists($file))->toBeFalse("$file should be removed with the appearance");
});
