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
}
