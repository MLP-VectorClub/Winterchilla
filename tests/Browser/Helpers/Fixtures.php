<?php

namespace Tests\Browser\Helpers;

/**
 * Test data that does not live in the SQL seed. Winterchilla's own seeder writes the cached deviations to Redis; another implementation under
 * test (CONTRACT_BASE_URL, see ApiClient) is asked through its testing-only fixture endpoints instead: PUT /test/deviations/{id},
 * PUT|DELETE /test/club-gallery/{id}.
 */
class Fixtures {
  /** Submission ID => [title, author] of the deviations TestSeeder caches */
  public const DEVIATIONS = [
    'd1b2c3d' => ['Seeded Entry', 'TestUser'],
    'd1b2c3e' => ['Admin Entry', 'TestUser'],
    'd1b2c3f' => ['Doomed Entry', 'TestUser'],
    'd1b2c3g' => ['Withdrawn Entry', 'TestUser'],
    'dfin001' => ['Finished Test Vector', 'TestUser'],
    'dfin002' => ['Vector For Finishing', 'TestAdmin'],
    'dfin003' => ['Vector For The UI Test', 'TestAdmin'],
    'dfin004' => ['Vector By Someone Else', 'TestUser'],
    'dfin005' => ['Image For New Posts', 'TestUser'],
    'dfin006' => ['Image For Changing', 'TestUser'],
    'dfin007' => ['Image For The UI Test', 'TestUser'],
  ];

  /** Makes the other implementation know the deviations the tests refer to, with images it does not have to fetch. */
  public static function seedExternalDeviations():void {
    if (!ApiClient::external())
      return;

    foreach (self::DEVIATIONS as $id => [$title, $author]) {
      $image = TestSeederConstants::baseUrl() . "/img/blank-pixel.png?d=$id";
      self::send('PUT', "/test/deviations/$id", [
        'preview' => $image,
        'fullsize' => $image,
        'title' => $title,
        'author' => $author,
        'type' => 'png',
        'provider' => 'fav.me',
      ]);
    }
  }

  public static function send(string $method, string $path, ?array $json = null):int {
    $ch = curl_init(rtrim(getenv('CONTRACT_BASE_URL'), '/') . $path);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    ]);
    if ($json !== null)
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status;
  }
}
