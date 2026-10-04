<?php

use Tests\Browser\Helpers\ApiClient;
use Tests\Browser\Helpers\AuthHelper;
use Tests\Browser\Helpers\Fixtures;
use Tests\Browser\Helpers\ServerManager;
use Tests\Browser\Helpers\TestSeederConstants;

uses(AuthHelper::class)
  ->beforeAll(function () {
    // Tests pointed at another implementation (CONTRACT_BASE_URL, see ApiClient, or UI_BASE_URL, see TestSeederConstants) need none of
    // Winterchilla's own server
    if (ApiClient::external() || TestSeederConstants::external()) {
      Fixtures::seedExternalDeviations();
      return;
    }

    $root = dirname(__DIR__, 2);
    $resetScript = $root . '/scripts/reset-test-db.sh';
    if (file_exists($resetScript)) {
      exec('bash ' . escapeshellarg($resetScript) . ' 2>&1', $output, $code);
      if ($code !== 0)
        throw new \RuntimeException('DB reset failed: ' . implode("\n", $output));
    }
    ServerManager::start();
  })
  ->afterAll(function () {
    if (ApiClient::external() || TestSeederConstants::external())
      return;
    ServerManager::stop();
  })
  ->in(__DIR__);
