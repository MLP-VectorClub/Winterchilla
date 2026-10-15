<?php

use App\InvalidTokenLimiter;
use PHPUnit\Framework\TestCase;

class InvalidTokenLimiterTest extends TestCase {
  private function arrayStore():object {
    return new class {
      /** @var array<string, int> */
      public array $counts = [];
      public int $ttl = 600;

      public function increment(string $key, int $ttl):int {
        $this->ttl = $ttl;

        return $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
      }

      public function count(string $key):int {
        return $this->counts[$key] ?? 0;
      }

      public function ttl(string $key):int {
        return $this->ttl;
      }
    };
  }

  protected function setUp():void {
    $_ENV['INVALID_TOKEN_LIMIT'] = '3';
    $_ENV['INVALID_TOKEN_WINDOW'] = '120';
  }

  protected function tearDown():void {
    InvalidTokenLimiter::useStore(null);
    unset($_ENV['INVALID_TOKEN_LIMIT'], $_ENV['INVALID_TOKEN_WINDOW']);
  }

  public function testAnAddressMayPresentInvalidTokensUpToTheLimit():void {
    $store = $this->arrayStore();
    InvalidTokenLimiter::useStore($store);

    InvalidTokenLimiter::recordInvalid('203.0.113.1');
    InvalidTokenLimiter::recordInvalid('203.0.113.1');
    self::assertNull(InvalidTokenLimiter::retryAfter('203.0.113.1'));

    InvalidTokenLimiter::recordInvalid('203.0.113.1');
    self::assertSame(120, InvalidTokenLimiter::retryAfter('203.0.113.1'));
  }

  public function testTheCountIsPerAddress():void {
    InvalidTokenLimiter::useStore($this->arrayStore());
    for ($i = 0; $i < 5; $i++)
      InvalidTokenLimiter::recordInvalid('203.0.113.1');

    self::assertNotNull(InvalidTokenLimiter::retryAfter('203.0.113.1'));
    self::assertNull(InvalidTokenLimiter::retryAfter('198.51.100.7'));
  }

  public function testTheWindowStartsWithTheFirstInvalidToken():void {
    $store = $this->arrayStore();
    InvalidTokenLimiter::useStore($store);
    InvalidTokenLimiter::recordInvalid('203.0.113.1');

    self::assertSame(120, $store->ttl);
  }

  public function testTheDefaultsApplyWithoutSettings():void {
    unset($_ENV['INVALID_TOKEN_LIMIT'], $_ENV['INVALID_TOKEN_WINDOW']);
    InvalidTokenLimiter::useStore($this->arrayStore());
    for ($i = 0; $i < InvalidTokenLimiter::DEFAULT_LIMIT - 1; $i++)
      InvalidTokenLimiter::recordInvalid('203.0.113.1');
    self::assertNull(InvalidTokenLimiter::retryAfter('203.0.113.1'));

    InvalidTokenLimiter::recordInvalid('203.0.113.1');
    self::assertSame(600, InvalidTokenLimiter::retryAfter('203.0.113.1'));
  }
}
