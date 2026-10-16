<?php

use App\NewSiteFeatures;
use PHPUnit\Framework\TestCase;

class NewSiteFeaturesTest extends TestCase {
  protected function tearDown():void {
    unset($_ENV['READ_ONLY_URL']);
  }

  public function testEveryFeatureIsComplete():void {
    foreach (NewSiteFeatures::all() as $key => $feature){
      self::assertMatchesRegularExpression('/^[a-z\d-]+$/', $key);
      self::assertNotSame('', $feature['title'], $key);
      self::assertNotSame('', $feature['summary'], $key);
      self::assertNotEmpty($feature['routes'], $key);
      foreach ($feature['routes'] as $route)
        self::assertStringStartsWith('/', $route, $key);
    }
  }

  public function testTheNewSiteAddressFillsInTheRouteParameters():void {
    $_ENV['READ_ONLY_URL'] = 'https://new.example/';
    $feature = NewSiteFeatures::find('sprite-colors');

    self::assertSame('https://new.example/cg/sprite-colors/42?token=a%20b', NewSiteFeatures::newSiteUrl($feature, ['id' => '42', 'guide' => 'pony'], '/cg/pony/sprite-colors/42?token=a%20b'));
  }

  public function testWithoutANewPathTheRequestedPathIsKept():void {
    $_ENV['READ_ONLY_URL'] = 'https://new.example';

    self::assertSame('https://new.example/some/thing', NewSiteFeatures::newSiteUrl(['title' => 'x', 'summary' => 'y', 'routes' => ['/some/thing']], [], '/some/thing'));
  }

  public function testNoAddressNoLink():void {
    unset($_ENV['READ_ONLY_URL']);

    self::assertNull(NewSiteFeatures::newSiteUrl(NewSiteFeatures::find('sprite-colors'), ['id' => '1'], '/cg/sprite-colors/1'));
  }

  public function testUnknownFeature():void {
    self::assertNull(NewSiteFeatures::find('nope'));
  }
}
