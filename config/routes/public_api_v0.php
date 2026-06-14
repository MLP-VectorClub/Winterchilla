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
$api_endpoint('/appearances', [\App\Controllers\API\AppearancesController::class, 'queryPublic']);
$api_endpoint('/appearances/all', [\App\Controllers\API\AppearancesController::class, 'queryAll']);
$api_endpoint('/appearances/[i:id]/sprite', [\App\Controllers\API\AppearancesController::class, 'sprite']);
$api_endpoint('/appearances/[i:id]/color-groups', [\App\Controllers\API\AppearancesController::class, 'getColorGroups']);
$api_endpoint('/users/me', [\App\Controllers\API\UsersController::class, 'me']);
$api_endpoint('/about/server', [\App\Controllers\API\AboutController::class, 'server']);
$api_endpoint('/about/upcoming', [\App\Controllers\AboutController::class, 'upcoming']);
$api_endpoint('/admin/logs/details/[i:id]', [\App\Controllers\AdminController::class, 'logDetail']);
$api_endpoint('/admin/usefullinks/[i:id]?', [\App\Controllers\AdminController::class, 'usefulLinksApi']);
$api_endpoint('/admin/usefullinks/reorder', [\App\Controllers\AdminController::class, 'reorderUsefulLinks']);
$api_endpoint('/admin/notices/[i:id]?', [\App\Controllers\AdminController::class, 'noticesApi']);
$api_endpoint('/admin/stat-cache', [\App\Controllers\AdminController::class, 'statCacheApi']);
$api_endpoint('/cg/appearances', [\App\Controllers\AppearanceController::class, 'autocomplete']);
$api_endpoint('/cg/appearance/[i:id]?', [\App\Controllers\AppearanceController::class, 'api']);
$api_endpoint('/cg/appearance/[i:id]/colorgroups', [\App\Controllers\AppearanceController::class, 'colorGroupsApi']);
$api_endpoint('/cg/appearance/[i:id]/sprite', [\App\Controllers\AppearanceController::class, 'spriteApi']);
$api_endpoint('/cg/appearance/[i:id]/relations', [\App\Controllers\AppearanceController::class, 'relationsApi']);
$api_endpoint('/cg/appearance/[i:id]/cutiemarks', [\App\Controllers\AppearanceController::class, 'cutiemarkApi']);
$api_endpoint('/cg/appearance/[i:id]/tagged', [\App\Controllers\AppearanceController::class, 'taggedApi']);
$api_endpoint('/cg/appearance/[i:id]/template', [\App\Controllers\AppearanceController::class, 'applyTemplate']);
$api_endpoint('/cg/appearance/[i:id]/sanitize-svg', [\App\Controllers\AppearanceController::class, 'sanitizeSvg']);
$api_endpoint('/cg/appearance/[i:id]/selective', [\App\Controllers\AppearanceController::class, 'selectiveClear']);
$api_endpoint('/cg/appearance/[i:id]/guide-relations', [\App\Controllers\AppearanceController::class, 'guideRelationsApi']);
$api_endpoint('/cg/appearance/[i:id]/pin', [\App\Controllers\AppearanceController::class, 'pinApi']);
$api_endpoint('/cg/full/reorder', [\App\Controllers\ColorGuideController::class, 'reorderFullList']);
$api_endpoint('/cg/export', [\App\Controllers\ColorGuideController::class, 'export']);
$api_endpoint('/cg/reindex', [\App\Controllers\ColorGuideController::class, 'reindex']);
$api_endpoint('/cg/tags', [\App\Controllers\TagController::class, 'autocomplete']);
$api_endpoint('/cg/tags/recount-uses', [\App\Controllers\TagController::class, 'recountUses']);
$api_endpoint('/cg/tag/[i:id]?', [\App\Controllers\TagController::class, 'api']);
$api_endpoint('/cg/tag/[i:id]/synonym', [\App\Controllers\TagController::class, 'synonymApi']);
$api_endpoint('/cg/colorgroup/[i:id]?', [\App\Controllers\ColorGroupController::class, 'api']);
$api_endpoint('/da-auth/status', [\App\Controllers\AuthController::class, 'sessionStatus']);
$api_endpoint('/da-auth/sign-out', [\App\Controllers\AuthController::class, 'signOut']);
$api_endpoint('/show/[i:id]?', [\App\Controllers\ShowController::class, 'api']);
$api_endpoint('/show/[i:id]/posts', [\App\Controllers\ShowController::class, 'postList']);
$api_endpoint('/show/[i:id]/vote', [\App\Controllers\ShowController::class, 'voteApi']);
$api_endpoint('/show/[i:id]/guide-relations', [\App\Controllers\ShowController::class, 'guideRelationsApi']);
$api_endpoint('/show/next', [\App\Controllers\ShowController::class, 'next']);
$api_endpoint('/show/prefill', [\App\Controllers\ShowController::class, 'prefill']);
$api_endpoint('/event/[i:id]?', [\App\Controllers\EventController::class, 'api']);
$api_endpoint('/event/[i:id]/finalize', [\App\Controllers\EventController::class, 'finalize']);
$api_endpoint('/event/[i:id]/check-entries', [\App\Controllers\EventController::class, 'checkEntries']);
$api_endpoint('/event/[i:id]/entry', [\App\Controllers\EventEntryController::class, 'api']);
$api_endpoint('/event/entry/[i:entryid]', [\App\Controllers\EventEntryController::class, 'api']);
$api_endpoint('/event/entry/[i:entryid]/lazyload', [\App\Controllers\EventEntryController::class, 'lazyload']);
$api_endpoint('/notif', [\App\Controllers\NotificationsController::class, 'get']);
$api_endpoint('/notif/[i:id]/mark-read', [\App\Controllers\NotificationsController::class, 'markRead']);
$api_endpoint('/post/[i:id]?', [\App\Controllers\PostController::class, 'api']);
$api_endpoint('/post/[i:id]/lazyload', [\App\Controllers\PostController::class, 'lazyload']);
$api_endpoint('/post/[i:id]/finish', [\App\Controllers\PostController::class, 'finishApi']);
$api_endpoint('/post/[i:id]/locate', [\App\Controllers\PostController::class, 'locate']);
$api_endpoint('/post/[i:id]/reload', [\App\Controllers\PostController::class, 'reload']);
$api_endpoint('/post/[i:id]/unbreak', [\App\Controllers\PostController::class, 'unbreak']);
$api_endpoint('/post/[i:id]/approval', [\App\Controllers\PostController::class, 'approvalApi']);
$api_endpoint('/post/[i:id]/image', [\App\Controllers\PostController::class, 'setImage']);
$api_endpoint('/post/[i:id]/reservation', [\App\Controllers\PostController::class, 'reservationApi']);
$api_endpoint('/post/check-image', [\App\Controllers\PostController::class, 'checkImage']);
$api_endpoint('/post/reservation', [\App\Controllers\PostController::class, 'addReservation']);
$api_endpoint('/post/request/[i:id]', [\App\Controllers\PostController::class, 'deleteRequest']);
$api_endpoint('/post/request/suggestion', [\App\Controllers\PostController::class, 'suggestRequest']);
$api_endpoint('/setting/[au:key]', [\App\Controllers\SettingController::class, 'api']);
$api_endpoint('/user/session/[i:id]', [\App\Controllers\UserController::class, 'sessionApi']);
$api_endpoint('/user/password', [\App\Controllers\UserController::class, 'passwordApi']);
$api_endpoint('/user/verify', [\App\Controllers\UserController::class, 'verifyApi']);
$api_endpoint('/user/[i:id]/avatar-wrap', [\App\Controllers\UserController::class, 'avatarWrap']);
$api_endpoint('/user/[i:id]/contrib-cache', [\App\Controllers\UserController::class, 'contribCacheApi']);
$api_endpoint('/user/[i:id]/role', [\App\Controllers\UserController::class, 'roleApi']);
$api_endpoint('/user/[i:id]/email', [\App\Controllers\UserController::class, 'emailApi']);
$api_endpoint('/user/[i:id]/preference/[au:key]', [\App\Controllers\PreferenceController::class, 'api']);
$api_endpoint('/user/[i:id]/pcg/point-history/recalc', [\App\Controllers\PersonalGuideController::class, 'pointRecalc']);
$api_endpoint('/user/[i:id]/pcg/points', [\App\Controllers\PersonalGuideController::class, 'pointsApi']);
$api_endpoint('/user/[i:id]/pcg/slots', [\App\Controllers\PersonalGuideController::class, 'slotsApi']);
$api_endpoint('/discord-connect/sync/[i:user_id]', [\App\Controllers\DiscordAuthController::class, 'sync']);
$api_endpoint('/discord-connect/unlink/[i:user_id]', [\App\Controllers\DiscordAuthController::class, 'unlink']);
