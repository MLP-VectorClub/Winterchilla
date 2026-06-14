<?php

namespace App;

global $router;

/**
 * @file
 * List of API v0 endpoints
 * These endpoints may change as needed until v1 is released
 */

/**
 * Allowing all request methods lets us reply with HTTP 405 to unsupported methods at the controller level
 *
 * @param string $path
 * @param array{0: class-string, 1: string} $target
 *
 * @return void
 */
$api_endpoint = function ($path, array $target) use ($router) {
  $router->map('POST|GET|PUT|DELETE', PUBLIC_API_V0_PATH.$path, $target);
};
$api_endpoint('/appearances', [\App\Controllers\API\AppearancesAPIController::class, 'queryPublic']);
$api_endpoint('/appearances/all', [\App\Controllers\API\AppearancesAPIController::class, 'queryAll']);
$api_endpoint('/appearances/[i:id]/sprite', [\App\Controllers\API\AppearancesAPIController::class, 'sprite']);
$api_endpoint('/appearances/[i:id]/color-groups', [\App\Controllers\API\AppearancesAPIController::class, 'getColorGroups']);
$api_endpoint('/users/me', [\App\Controllers\API\UsersAPIController::class, 'me']);
$api_endpoint('/about/server', [\App\Controllers\API\AboutAPIController::class, 'server']);
$api_endpoint('/about/upcoming', [\App\Controllers\API\AboutAPIController::class, 'upcoming']);
$api_endpoint('/admin/logs/details/[i:id]', [\App\Controllers\API\AdminAPIController::class, 'logDetail']);
$api_endpoint('/admin/usefullinks/[i:id]?', [\App\Controllers\API\AdminAPIController::class, 'usefulLinksApi']);
$api_endpoint('/admin/usefullinks/reorder', [\App\Controllers\API\AdminAPIController::class, 'reorderUsefulLinks']);
$api_endpoint('/admin/notices/[i:id]?', [\App\Controllers\API\AdminAPIController::class, 'noticesApi']);
$api_endpoint('/admin/stat-cache', [\App\Controllers\API\AdminAPIController::class, 'statCacheApi']);
$api_endpoint('/cg/appearances', [\App\Controllers\API\AppearanceAPIController::class, 'autocomplete']);
$api_endpoint('/cg/appearance/[i:id]?', [\App\Controllers\API\AppearanceAPIController::class, 'api']);
$api_endpoint('/cg/appearance/[i:id]/colorgroups', [\App\Controllers\API\AppearanceAPIController::class, 'colorGroupsApi']);
$api_endpoint('/cg/appearance/[i:id]/sprite', [\App\Controllers\API\AppearanceAPIController::class, 'spriteApi']);
$api_endpoint('/cg/appearance/[i:id]/relations', [\App\Controllers\API\AppearanceAPIController::class, 'relationsApi']);
$api_endpoint('/cg/appearance/[i:id]/cutiemarks', [\App\Controllers\API\AppearanceAPIController::class, 'cutiemarkApi']);
$api_endpoint('/cg/appearance/[i:id]/tagged', [\App\Controllers\API\AppearanceAPIController::class, 'taggedApi']);
$api_endpoint('/cg/appearance/[i:id]/template', [\App\Controllers\API\AppearanceAPIController::class, 'applyTemplate']);
$api_endpoint('/cg/appearance/[i:id]/sanitize-svg', [\App\Controllers\API\AppearanceAPIController::class, 'sanitizeSvg']);
$api_endpoint('/cg/appearance/[i:id]/selective', [\App\Controllers\API\AppearanceAPIController::class, 'selectiveClear']);
$api_endpoint('/cg/appearance/[i:id]/guide-relations', [\App\Controllers\API\AppearanceAPIController::class, 'guideRelationsApi']);
$api_endpoint('/cg/appearance/[i:id]/pin', [\App\Controllers\API\AppearanceAPIController::class, 'pinApi']);
$api_endpoint('/cg/full/reorder', [\App\Controllers\API\ColorGuideAPIController::class, 'reorderFullList']);
$api_endpoint('/cg/export', [\App\Controllers\API\ColorGuideAPIController::class, 'export']);
$api_endpoint('/cg/reindex', [\App\Controllers\API\ColorGuideAPIController::class, 'reindex']);
$api_endpoint('/cg/tags', [\App\Controllers\API\TagAPIController::class, 'autocomplete']);
$api_endpoint('/cg/tags/recount-uses', [\App\Controllers\API\TagAPIController::class, 'recountUses']);
$api_endpoint('/cg/tag/[i:id]?', [\App\Controllers\API\TagAPIController::class, 'api']);
$api_endpoint('/cg/tag/[i:id]/synonym', [\App\Controllers\API\TagAPIController::class, 'synonymApi']);
$api_endpoint('/cg/colorgroup/[i:id]?', [\App\Controllers\API\ColorGroupAPIController::class, 'api']);
$api_endpoint('/da-auth/status', [\App\Controllers\API\AuthAPIController::class, 'sessionStatus']);
$api_endpoint('/da-auth/sign-out', [\App\Controllers\API\AuthAPIController::class, 'signOut']);
$api_endpoint('/show/[i:id]?', [\App\Controllers\API\ShowAPIController::class, 'api']);
$api_endpoint('/show/[i:id]/posts', [\App\Controllers\API\ShowAPIController::class, 'postList']);
$api_endpoint('/show/[i:id]/vote', [\App\Controllers\API\ShowAPIController::class, 'voteApi']);
$api_endpoint('/show/[i:id]/guide-relations', [\App\Controllers\API\ShowAPIController::class, 'guideRelationsApi']);
$api_endpoint('/show/next', [\App\Controllers\API\ShowAPIController::class, 'next']);
$api_endpoint('/show/prefill', [\App\Controllers\API\ShowAPIController::class, 'prefill']);
$api_endpoint('/event/[i:id]?', [\App\Controllers\API\EventAPIController::class, 'api']);
$api_endpoint('/event/[i:id]/finalize', [\App\Controllers\API\EventAPIController::class, 'finalize']);
$api_endpoint('/event/[i:id]/check-entries', [\App\Controllers\API\EventAPIController::class, 'checkEntries']);
$api_endpoint('/event/[i:id]/entry', [\App\Controllers\API\EventEntryAPIController::class, 'api']);
$api_endpoint('/event/entry/[i:entryid]', [\App\Controllers\API\EventEntryAPIController::class, 'api']);
$api_endpoint('/event/entry/[i:entryid]/lazyload', [\App\Controllers\API\EventEntryAPIController::class, 'lazyload']);
$api_endpoint('/notif', [\App\Controllers\API\NotificationAPIController::class, 'get']);
$api_endpoint('/notif/[i:id]/mark-read', [\App\Controllers\API\NotificationAPIController::class, 'markRead']);
$api_endpoint('/post/[i:id]?', [\App\Controllers\API\PostAPIController::class, 'api']);
$api_endpoint('/post/[i:id]/lazyload', [\App\Controllers\API\PostAPIController::class, 'lazyload']);
$api_endpoint('/post/[i:id]/finish', [\App\Controllers\API\PostAPIController::class, 'finishApi']);
$api_endpoint('/post/[i:id]/locate', [\App\Controllers\API\PostAPIController::class, 'locate']);
$api_endpoint('/post/[i:id]/reload', [\App\Controllers\API\PostAPIController::class, 'reload']);
$api_endpoint('/post/[i:id]/unbreak', [\App\Controllers\API\PostAPIController::class, 'unbreak']);
$api_endpoint('/post/[i:id]/approval', [\App\Controllers\API\PostAPIController::class, 'approvalApi']);
$api_endpoint('/post/[i:id]/image', [\App\Controllers\API\PostAPIController::class, 'setImage']);
$api_endpoint('/post/[i:id]/reservation', [\App\Controllers\API\PostAPIController::class, 'reservationApi']);
$api_endpoint('/post/check-image', [\App\Controllers\API\PostAPIController::class, 'checkImage']);
$api_endpoint('/post/reservation', [\App\Controllers\API\PostAPIController::class, 'addReservation']);
$api_endpoint('/post/request/[i:id]', [\App\Controllers\API\PostAPIController::class, 'deleteRequest']);
$api_endpoint('/post/request/suggestion', [\App\Controllers\API\PostAPIController::class, 'suggestRequest']);
$api_endpoint('/setting/[au:key]', [\App\Controllers\API\SettingAPIController::class, 'api']);
$api_endpoint('/user/session/[i:id]', [\App\Controllers\API\UserAPIController::class, 'sessionApi']);
$api_endpoint('/user/password', [\App\Controllers\API\UserAPIController::class, 'passwordApi']);
$api_endpoint('/user/verify', [\App\Controllers\API\UserAPIController::class, 'verifyApi']);
$api_endpoint('/user/[i:id]/avatar-wrap', [\App\Controllers\API\UserAPIController::class, 'avatarWrap']);
$api_endpoint('/user/[i:id]/contrib-cache', [\App\Controllers\API\UserAPIController::class, 'contribCacheApi']);
$api_endpoint('/user/[i:id]/role', [\App\Controllers\API\UserAPIController::class, 'roleApi']);
$api_endpoint('/user/[i:id]/email', [\App\Controllers\API\UserAPIController::class, 'emailApi']);
$api_endpoint('/user/[i:id]/preference/[au:key]', [\App\Controllers\API\PreferenceAPIController::class, 'api']);
$api_endpoint('/user/[i:id]/pcg/point-history/recalc', [\App\Controllers\API\PersonalGuideAPIController::class, 'pointRecalc']);
$api_endpoint('/user/[i:id]/pcg/points', [\App\Controllers\API\PersonalGuideAPIController::class, 'pointsApi']);
$api_endpoint('/user/[i:id]/pcg/slots', [\App\Controllers\API\PersonalGuideAPIController::class, 'slotsApi']);
$api_endpoint('/discord-connect/sync/[i:user_id]', [\App\Controllers\DiscordAuthController::class, 'sync']);
$api_endpoint('/discord-connect/unlink/[i:user_id]', [\App\Controllers\DiscordAuthController::class, 'unlink']);
