<?php

use App\LunaSession;
use PHPUnit\Framework\TestCase;

class LunaSessionTest extends TestCase {
  /** Made by Luna's own Laravel encrypter (AES-256-CBC) with this key, for a cookie named test_session, see the cookie's value prefix */
  private const KEY = 'base64:a2tra2tra2tra2tra2tra2tra2tra2tra2tra2tra2s=';
  private const ID = 'Zk3dQmN8vTpL0aXrYb7cUeWfHgJiOsDq1n5MtVxC';
  private const COOKIE = 'eyJpdiI6ImQ0NGVkU2lod1FMbTBONjZ1bUdMbEE9PSIsInZhbHVlIjoibldpcUNYZjQyY1JEQzVrQ3Bnci8yMTBPQ2dxRXVIN3hzejBmQllNTG1YM0h0eHROS0dNY2xyUXNlenhDWFB2YURScEpURTA5c2F2eVRwM01RdXgwaFFSL2ZaZGd2V1lpbmtPNHJKSnBONTlJM0VGMDlFYmdhNWJNOTVwcEtJQ0oiLCJtYWMiOiIzYjI5YzZiNTYxOGY5MzA0ODEwMThjYWFhNWQ3YzdlNGI0NzY3NDRkNjAwZWE5YjgzYzc5MTE4NjFlODJiMjQxIiwidGFnIjoiIn0=';

  public function testReadsTheSessionIdOutOfALaravelCookie():void {
    self::assertSame(self::ID, LunaSession::sessionIdFromCookie(self::COOKIE, 'test_session', self::KEY));
  }

  public function testRefusesACookieMadeWithAnotherKey():void {
    $other_key = 'base64:'.base64_encode(str_repeat('x', 32));
    self::assertNull(LunaSession::sessionIdFromCookie(self::COOKIE, 'test_session', $other_key));
  }

  public function testRefusesACookieThatBelongsToAnotherCookieName():void {
    // The prefix of the value is made from the name, so one cookie cannot be passed off as another
    self::assertNull(LunaSession::sessionIdFromCookie(self::COOKIE, 'other_session', self::KEY));
  }

  public function testRefusesDamagedAndForgedCookies():void {
    self::assertNull(LunaSession::sessionIdFromCookie('', 'test_session', self::KEY));
    self::assertNull(LunaSession::sessionIdFromCookie('not a cookie', 'test_session', self::KEY));
    self::assertNull(LunaSession::sessionIdFromCookie(base64_encode('{"iv":"a","value":"b","mac":"c"}'), 'test_session', self::KEY));
    // One changed character in the encrypted value breaks the MAC
    $payload = json_decode(base64_decode(self::COOKIE), true);
    $payload['value'] = 'A'.substr($payload['value'], 1);
    self::assertNull(LunaSession::sessionIdFromCookie(base64_encode(json_encode($payload)), 'test_session', self::KEY));
  }

  public function testRefusesAKeyThatIsNotAKey():void {
    self::assertNull(LunaSession::sessionIdFromCookie(self::COOKIE, 'test_session', ''));
    self::assertNull(LunaSession::sessionIdFromCookie(self::COOKIE, 'test_session', 'base64:'.base64_encode('short')));
  }

  public function testASessionLastsAsLongAsLunaSaysAfterItsLastRequest():void {
    $now = 1_800_000_000;
    self::assertTrue(LunaSession::isAlive($now - 43200 * 60, 43200, $now));
    self::assertTrue(LunaSession::isAlive($now - 60, 120, $now));
    self::assertFalse(LunaSession::isAlive($now - 43200 * 60 - 1, 43200, $now));
    self::assertFalse(LunaSession::isAlive($now - 121 * 60, 120, $now));
  }

  /** Made by Luna's own encrypter, `encryptString('ponyfan@example.com')` with the same key as above (an address is not prefixed like a cookie) */
  private const ENCRYPTED_EMAIL = 'eyJpdiI6Ik5zc0N0RXBDaU1HNk9yNm56Y3JjUnc9PSIsInZhbHVlIjoiMWVXWGpCVmpDY0swRW9PejFWVjdlbWNaRFczOXBMcHhCR2FEYjcyUjRHOD0iLCJtYWMiOiI4ZmYxYTJmZGY5M2Q0YzhlMDc5MGI1ZTIwNzZiNDU2MGQwMzZlNGFkODhmNjNkZTI1Mjg0NTQ3ZTU1YmMxMmNlIiwidGFnIjoiIn0=';

  public function testDecryptsAnAddressTheWayLunaStoresIt():void {
    self::assertSame('ponyfan@example.com', LunaSession::decryptPayload(self::ENCRYPTED_EMAIL, self::KEY));
    self::assertNull(LunaSession::decryptPayload(self::ENCRYPTED_EMAIL, 'base64:'.base64_encode(str_repeat('x', 32))));
  }

  public function testReadsPlainAndEncryptedAddressesFromTheTable():void {
    $_ENV['LUNA_APP_KEY'] = self::KEY;
    self::assertSame('plain@example.com', LunaSession::emailFromStored('plain@example.com'));
    self::assertSame('ponyfan@example.com', LunaSession::emailFromStored(self::ENCRYPTED_EMAIL));
    self::assertNull(LunaSession::emailFromStored(null));
    self::assertNull(LunaSession::emailFromStored(''));
    unset($_ENV['LUNA_APP_KEY']);
    // Without the key an encrypted address is none, and never the ciphertext
    self::assertNull(LunaSession::emailFromStored(self::ENCRYPTED_EMAIL));
    self::assertSame('plain@example.com', LunaSession::emailFromStored('plain@example.com'));
  }
}
