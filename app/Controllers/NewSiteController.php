<?php

namespace App\Controllers;

use App\CoreUtils;
use App\HTTP;
use App\NewSiteFeatures;

/**
 * The page for addresses of features that only exist on the new site, see `config/new_site_only.php`
 */
class NewSiteController extends Controller {
  /**
   * @param array<string, mixed> $params route parameters
   * @param string|null $key the feature's key in `config/new_site_only.php`
   */
  public function unavailable($params, $key = null) {
    $feature = $key === null ? null : NewSiteFeatures::find($key);
    if ($feature === null)
      CoreUtils::notFound();
    if ($this->action !== 'GET')
      CoreUtils::notAllowed();

    HTTP::statusCode(200);
    CoreUtils::loadPage(__METHOD__, [
      'title' => $feature['title'].' is on the new site',
      'noindex' => true,
      'import' => [
        'feature' => $feature,
        'new_site_url' => NewSiteFeatures::newSiteUrl($feature, is_array($params) ? $params : [], $_SERVER['REQUEST_URI']),
      ],
    ]);
  }
}
