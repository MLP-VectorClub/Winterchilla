<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\TestSeederConstants;

// DELETE /appearances/{id} (staff only); the rest of the contract is in AppearanceContractTest.php.

it('removes the cutie mark files along with a deleted appearance', function () {
  $fs = dirname(__DIR__, 3) . '/fs/';
  $id = TestSeederConstants::DELETABLE_CUTIEMARK_ID;
  $files = ["{$fs}cm_source/$id.svg", "{$fs}cm_tokenized/$id.svg", "{$fs}cg_render/cutiemark/$id.svg"];
  foreach ($files as $file)
    expect(file_exists($file))->toBeTrue("$file should be seeded");

  $appearance_path = '/appearances/' . TestSeederConstants::DELETABLE_APPEARANCE_ID;
  expect(ApiClient::guest()->request('DELETE', $appearance_path)['status'])->toBe(401);
  expect(ApiClient::loggedInAs(TestSeederConstants::USER_ID)->request('DELETE', $appearance_path)['status'])->toBe(403);
  foreach ($files as $file)
    expect(file_exists($file))->toBeTrue("$file should survive a rejected delete");

  $r = ApiClient::loggedInAs(TestSeederConstants::ADMIN_ID)->request('DELETE', $appearance_path);
  expect($r['status'])->toBe(204);

  foreach ($files as $file)
    expect(file_exists($file))->toBeFalse("$file should be removed with the appearance");
});
