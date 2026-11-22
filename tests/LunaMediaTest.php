<?php

use App\LunaMedia;
use PHPUnit\Framework\TestCase;

class LunaMediaTest extends TestCase {
  protected function setUp():void {
    unset($_GET['token']);
  }

  public function testMediaPath():void {
    // media 284 is 320 in base 36 (36 + 284), which is "8w"
    self::assertSame('sprites/8w/abc.png', LunaMedia::mediaPath(284, 'sprites', 'abc.png'));
    self::assertSame('sprites/8w/conversions/abc-2x.png', LunaMedia::mediaPath(284, 'sprites', 'abc.png', '2x'));
    self::assertSame('cutiemarks/10/x.svg', LunaMedia::mediaPath(0, 'cutiemarks', 'x.svg'));
  }

  public function testApiUrls():void {
    self::assertSame('https://api.mlpvector.club/appearances/5/image?type=palette&format=png', LunaMedia::paletteImage(5));
    self::assertSame('https://api.mlpvector.club/appearances/5/image?type=facing&format=svg&facing=right', LunaMedia::facingImage(5, 'right'));
    self::assertSame('https://api.mlpvector.club/appearances/5/image?type=facing&format=svg&facing=left', LunaMedia::facingImage(5, 'bogus'));
    self::assertSame('https://api.mlpvector.club/appearances/5/palette?format=gpl', LunaMedia::swatches(5, 'gpl'));
    self::assertSame('https://api.mlpvector.club/cutie-marks/9/download', LunaMedia::cutieMark(9, true));
  }

  public function testShareTokenIsPassedOn():void {
    $_GET['token'] = 'a b&c';
    self::assertSame('https://api.mlpvector.club/cutie-marks/9/image?token=a+b%26c', LunaMedia::cutieMark(9));
  }
}
