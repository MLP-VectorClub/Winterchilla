<?php

use Tests\Browser\Helpers\AuthHelper;
use Tests\Browser\Helpers\ServerManager;

uses(AuthHelper::class)->in(__DIR__);

beforeAll(function () {
  ServerManager::start();
});

afterAll(function () {
  ServerManager::stop();
});
