<?php

namespace Tests\Browser\Helpers;

trait AuthHelper {
  protected function loginAs(int $userId, string $path = '/'): mixed {
    return visit(TestSeederConstants::BASE_URL . '/test-login/' . $userId)
      ->navigate(TestSeederConstants::BASE_URL . $path);
  }

  protected function loginAsAdmin(string $path = '/'): mixed {
    return $this->loginAs(TestSeederConstants::ADMIN_ID, $path);
  }

  protected function loginAsUser(string $path = '/'): mixed {
    return $this->loginAs(TestSeederConstants::USER_ID, $path);
  }
}
