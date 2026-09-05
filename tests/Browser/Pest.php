<?php

use Tests\Browser\Helpers\AuthHelper;
use Tests\Browser\Helpers\ServerManager;

uses(AuthHelper::class)
  ->beforeAll(function () {
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
    ServerManager::stop();
  })
  ->in(__DIR__);
