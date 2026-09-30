<?php

namespace App;

global $router;

/**
 * @file
 * List of API v0 endpoints
 * These endpoints may change as needed until v1 is released
 */

/**
 * Paths follow Luna's resource style (see docs/api-path-alignment.md). Allowing all request methods lets us reply
 * with HTTP 405 to unsupported methods at the controller level; pass $methods when several controllers share a path.
 *
 * @param string $path
 * @param array{0: class-string, 1: string} $target
 * @param string $methods
 *
 * @return void
 */
$api_endpoint = function ($path, array $target, $methods = 'POST|GET|PUT|DELETE') use ($router) {
  $router->map($methods, PUBLIC_API_V0_PATH.$path, $target);
};
$api_endpoint('/appearances', [\App\Controllers\API\AppearancesAPIController::class, 'queryPublic'], 'GET');
$api_endpoint('/appearances', [\App\Controllers\API\AppearanceAPIController::class, 'api'], 'POST');
$api_endpoint('/appearances/all', [\App\Controllers\API\AppearancesAPIController::class, 'queryAll']);
$api_endpoint('/appearances/pinned', [\App\Controllers\API\AppearancesAPIController::class, 'pinned']);
$api_endpoint('/appearances/autocomplete', [\App\Controllers\API\AppearanceAPIController::class, 'autocomplete']);
$api_endpoint('/appearances/order', [\App\Controllers\API\ColorGuideAPIController::class, 'reorderFullList']);
$api_endpoint('/appearances/[i:id]', [\App\Controllers\API\AppearancesAPIController::class, 'get'], 'GET');
$api_endpoint('/appearances/[i:id]', [\App\Controllers\API\AppearanceAPIController::class, 'api'], 'PUT|DELETE');
$api_endpoint('/appearances/[i:id]/metadata', [\App\Controllers\API\AppearanceAPIController::class, 'api']);
$api_endpoint('/appearances/[i:id]/locate', [\App\Controllers\API\AppearancesAPIController::class, 'locate']);
$api_endpoint('/appearances/[i:id]/preview', [\App\Controllers\API\AppearancesAPIController::class, 'preview']);
$api_endpoint('/appearances/[i:id]/sprite', [\App\Controllers\API\AppearancesAPIController::class, 'sprite'], 'GET');
$api_endpoint('/appearances/[i:id]/sprite', [\App\Controllers\API\AppearanceAPIController::class, 'spriteApi'], 'POST|PUT|DELETE');
$api_endpoint('/appearances/[i:id]/color-groups', [\App\Controllers\API\AppearancesAPIController::class, 'getColorGroups']);
$api_endpoint('/appearances/[i:id]/color-groups/order', [\App\Controllers\API\AppearanceAPIController::class, 'colorGroupsApi']);
$api_endpoint('/appearances/[i:id]/relations', [\App\Controllers\API\AppearanceAPIController::class, 'relationsApi']);
$api_endpoint('/appearances/[i:id]/cutie-marks', [\App\Controllers\API\AppearanceAPIController::class, 'cutiemarkApi']);
$api_endpoint('/appearances/[i:id]/tags', [\App\Controllers\API\AppearanceAPIController::class, 'taggedApi']);
$api_endpoint('/appearances/[i:id]/template', [\App\Controllers\API\AppearanceAPIController::class, 'applyTemplate']);
$api_endpoint('/appearances/[i:id]/sanitize-svg', [\App\Controllers\API\AppearanceAPIController::class, 'sanitizeSvg']);
$api_endpoint('/appearances/[i:id]/contents', [\App\Controllers\API\AppearanceAPIController::class, 'selectiveClear']);
$api_endpoint('/appearances/[i:id]/shows', [\App\Controllers\API\AppearanceAPIController::class, 'guideRelationsApi']);
$api_endpoint('/appearances/[i:id]/pin', [\App\Controllers\API\AppearanceAPIController::class, 'pinApi']);
$api_endpoint('/users/me', [\App\Controllers\API\UsersAPIController::class, 'me']);
$api_endpoint('/users', [\App\Controllers\API\UsersAPIController::class, 'list'], 'GET');
$api_endpoint('/users/da/[un:username]', [\App\Controllers\API\UsersAPIController::class, 'getByName']);
$api_endpoint('/users/[i:id]', [\App\Controllers\API\UsersAPIController::class, 'getById'], 'GET');
$api_endpoint('/users/[i:id]/profile', [\App\Controllers\API\UsersAPIController::class, 'profile'], 'GET');
$api_endpoint('/users/[i:id]/contributions/[(cms-provided|requests|reservations|finished-posts|fulfilled-requests):type]', [\App\Controllers\API\UsersAPIController::class, 'contributions'], 'GET');
$api_endpoint('/config', [\App\Controllers\API\ConfigAPIController::class, 'get']);
$api_endpoint('/about/connection', [\App\Controllers\API\AboutAPIController::class, 'connection']);
$api_endpoint('/about/members', [\App\Controllers\API\AboutAPIController::class, 'members']);
$api_endpoint('/about/upcoming', [\App\Controllers\API\AboutAPIController::class, 'upcoming']);
$api_endpoint('/admin/logs', [\App\Controllers\API\AdminAPIController::class, 'logList'], 'GET');
$api_endpoint('/admin/logs/[i:id]', [\App\Controllers\API\AdminAPIController::class, 'logDetail']);
$api_endpoint('/useful-links', [\App\Controllers\API\AdminAPIController::class, 'usefulLinksApi'], 'POST');
$api_endpoint('/useful-links/[i:id]', [\App\Controllers\API\AdminAPIController::class, 'usefulLinksApi']);
$api_endpoint('/useful-links/order', [\App\Controllers\API\AdminAPIController::class, 'reorderUsefulLinks']);
$api_endpoint('/notices', [\App\Controllers\API\NoticesAPIController::class, 'list'], 'GET');
$api_endpoint('/notices', [\App\Controllers\API\NoticesAPIController::class, 'api'], 'POST');
$api_endpoint('/notices/current', [\App\Controllers\API\NoticesAPIController::class, 'current'], 'GET');
$api_endpoint('/notices/[i:id]', [\App\Controllers\API\NoticesAPIController::class, 'api']);
$api_endpoint('/admin/stat-cache', [\App\Controllers\API\AdminAPIController::class, 'statCacheApi']);
$api_endpoint('/cg/full', [\App\Controllers\API\ColorGuideAPIController::class, 'fullList']);
$api_endpoint('/color-guide', [\App\Controllers\API\ColorGuideAPIController::class, 'index']);
$api_endpoint('/color-guide/major-changes', [\App\Controllers\API\ColorGuideAPIController::class, 'majorChanges']);
$api_endpoint('/color-guide/export', [\App\Controllers\API\ColorGuideAPIController::class, 'export']);
$api_endpoint('/color-guide/reindex', [\App\Controllers\API\ColorGuideAPIController::class, 'reindex']);
$api_endpoint('/tags', [\App\Controllers\API\TagAPIController::class, 'list'], 'GET');
$api_endpoint('/tags/autocomplete', [\App\Controllers\API\TagAPIController::class, 'autocomplete'], 'GET');
$api_endpoint('/tags/recount-uses', [\App\Controllers\API\TagAPIController::class, 'recountUses']);
$api_endpoint('/tags', [\App\Controllers\API\TagAPIController::class, 'api'], 'POST');
$api_endpoint('/tags/[i:id]', [\App\Controllers\API\TagAPIController::class, 'api']);
$api_endpoint('/tags/[i:id]/synonym', [\App\Controllers\API\TagAPIController::class, 'synonymApi']);
$api_endpoint('/color-groups', [\App\Controllers\API\ColorGroupAPIController::class, 'api'], 'POST');
$api_endpoint('/color-groups/[i:id]', [\App\Controllers\API\ColorGroupAPIController::class, 'api']);
$api_endpoint('/users/session/status', [\App\Controllers\API\AuthAPIController::class, 'sessionStatus']);
$api_endpoint('/users/signout', [\App\Controllers\API\AuthAPIController::class, 'signOut']);
$api_endpoint('/show', [\App\Controllers\API\ShowAPIController::class, 'list'], 'GET');
$api_endpoint('/show', [\App\Controllers\API\ShowAPIController::class, 'api'], 'POST');
$api_endpoint('/show/[i:id]', [\App\Controllers\API\ShowAPIController::class, 'api']);
$api_endpoint('/show/[i:id]/posts', [\App\Controllers\API\ShowAPIController::class, 'postList']);
$api_endpoint('/show/[i:id]/vote', [\App\Controllers\API\ShowAPIController::class, 'voteApi']);
$api_endpoint('/show/[i:id]/appearances', [\App\Controllers\API\ShowAPIController::class, 'guideRelationsApi']);
$api_endpoint('/show/next', [\App\Controllers\API\ShowAPIController::class, 'next']);
$api_endpoint('/show/prefill', [\App\Controllers\API\ShowAPIController::class, 'prefill']);
$api_endpoint('/events', [\App\Controllers\API\EventAPIController::class, 'list'], 'GET');
$api_endpoint('/events', [\App\Controllers\API\EventAPIController::class, 'api'], 'POST');
$api_endpoint('/events/[i:id]', [\App\Controllers\API\EventAPIController::class, 'api']);
$api_endpoint('/events/[i:id]/finalize', [\App\Controllers\API\EventAPIController::class, 'finalize']);
$api_endpoint('/events/[i:id]/entries/check', [\App\Controllers\API\EventAPIController::class, 'checkEntries']);
$api_endpoint('/events/[i:id]/entries', [\App\Controllers\API\EventEntryAPIController::class, 'api']);
$api_endpoint('/event-entries/[i:entryid]', [\App\Controllers\API\EventEntryAPIController::class, 'api']);
$api_endpoint('/event-entries/[i:entryid]/lazyload', [\App\Controllers\API\EventEntryAPIController::class, 'lazyload']);
$api_endpoint('/notifications', [\App\Controllers\API\NotificationAPIController::class, 'get']);
$api_endpoint('/notifications/[i:id]/read', [\App\Controllers\API\NotificationAPIController::class, 'markRead']);
$api_endpoint('/posts', [\App\Controllers\API\PostAPIController::class, 'list'], 'GET');
$api_endpoint('/posts', [\App\Controllers\API\PostAPIController::class, 'api'], 'POST');
$api_endpoint('/posts/[i:id]', [\App\Controllers\API\PostAPIController::class, 'api']);
$api_endpoint('/posts/[i:id]/lazyload', [\App\Controllers\API\PostAPIController::class, 'lazyload']);
$api_endpoint('/posts/[i:id]/finish', [\App\Controllers\API\PostAPIController::class, 'finishApi']);
$api_endpoint('/posts/[i:id]/location', [\App\Controllers\API\PostAPIController::class, 'locate']);
$api_endpoint('/posts/[i:id]/reload', [\App\Controllers\API\PostAPIController::class, 'reload']);
$api_endpoint('/posts/[i:id]/unbreak', [\App\Controllers\API\PostAPIController::class, 'unbreak']);
$api_endpoint('/posts/[i:id]/approval', [\App\Controllers\API\PostAPIController::class, 'approvalApi']);
$api_endpoint('/posts/[i:id]/image', [\App\Controllers\API\PostAPIController::class, 'setImage']);
$api_endpoint('/posts/[i:id]/reservation', [\App\Controllers\API\PostAPIController::class, 'reservationApi']);
$api_endpoint('/posts/check-image', [\App\Controllers\API\PostAPIController::class, 'checkImage']);
$api_endpoint('/posts/reservations', [\App\Controllers\API\PostAPIController::class, 'addReservation']);
$api_endpoint('/posts/requests/[i:id]', [\App\Controllers\API\PostAPIController::class, 'deleteRequest']);
$api_endpoint('/posts/requests/suggestion', [\App\Controllers\API\PostAPIController::class, 'suggestRequest']);
$api_endpoint('/useful-links/sidebar', [\App\Controllers\API\UsefulLinksAPIController::class, 'sidebar']);
$api_endpoint('/user-prefs/me', [\App\Controllers\API\UserPrefsAPIController::class, 'me']);
$api_endpoint('/settings/[au:key]', [\App\Controllers\API\SettingAPIController::class, 'api']);
$api_endpoint('/users/sessions/[i:id]', [\App\Controllers\API\UserAPIController::class, 'sessionApi']);
$api_endpoint('/users/me/password', [\App\Controllers\API\UserAPIController::class, 'passwordApi']);
$api_endpoint('/users/email/verify', [\App\Controllers\API\UserAPIController::class, 'verifyApi']);
$api_endpoint('/users/contributions/lazyload/[favme:favme]', [\App\Controllers\API\UserAPIController::class, 'contribLazyload']);
$api_endpoint('/users/[i:id]/avatar-wrap', [\App\Controllers\API\UserAPIController::class, 'avatarWrap']);
$api_endpoint('/users/[i:id]/contributions/cache', [\App\Controllers\API\UserAPIController::class, 'contribCacheApi']);
$api_endpoint('/users/[i:id]/role', [\App\Controllers\API\UserAPIController::class, 'roleApi']);
$api_endpoint('/users/[i:id]/email-changes', [\App\Controllers\API\UserAPIController::class, 'emailApi']);
$api_endpoint('/users/[i:id]/preferences/[au:key]', [\App\Controllers\API\PreferenceAPIController::class, 'api']);
$api_endpoint('/users/[i:id]/personal-guide/point-history', [\App\Controllers\API\PersonalGuideAPIController::class, 'pointHistory'], 'GET');
$api_endpoint('/users/[i:id]/personal-guide/point-history/recalculation', [\App\Controllers\API\PersonalGuideAPIController::class, 'pointRecalc']);
$api_endpoint('/users/[i:id]/personal-guide/points', [\App\Controllers\API\PersonalGuideAPIController::class, 'pointsApi']);
$api_endpoint('/users/[i:id]/personal-guide/slots', [\App\Controllers\API\PersonalGuideAPIController::class, 'slotsApi']);
$api_endpoint('/users/[i:user_id]/discord/sync', [\App\Controllers\DiscordAuthController::class, 'sync']);
$api_endpoint('/users/[i:user_id]/discord', [\App\Controllers\DiscordAuthController::class, 'unlink']);
