<?php

namespace Tests\Browser\Helpers;

use function Pest\Browser\page;

trait AuthHelper {
  public function loginAs(int $userId): void {
    page()->goto(TestSeederConstants::BASE_URL . "/test-login/$userId");
    page()->waitForURL(TestSeederConstants::BASE_URL . '/');
  }

  public function loginAsRole(string $role): void {
    $this->loginAs(match ($role) {
      'user'  => TestSeederConstants::USER_ID,
      'admin' => TestSeederConstants::ADMIN_ID,
      default => throw new \InvalidArgumentException("Unknown test role: $role"),
    });
  }
}
