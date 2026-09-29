<?php

namespace App\Testing;

use League\OAuth2\Client\Token\AccessToken;
use SeinopSys\OAuth2\Client\Provider\DeviantArtProvider;

/**
 * DeviantArt OAuth client pointed at the TEST_MODE fake provider (TestOAuthController) instead of
 * deviantart.com. The upstream provider hard-codes its URLs, hence the subclass.
 */
class TestDeviantArtProvider extends DeviantArtProvider {
  public function getBaseAuthorizationUrl() {
    return FakeOAuth::baseUrl('deviantart').'/oauth2/authorize';
  }

  public function getBaseAccessTokenUrl(array $params) {
    return FakeOAuth::baseUrl('deviantart').'/oauth2/token';
  }

  public function getResourceOwnerDetailsUrl(AccessToken $token) {
    return FakeOAuth::baseUrl('deviantart').'/api/v1/oauth2/user/whoami';
  }
}
