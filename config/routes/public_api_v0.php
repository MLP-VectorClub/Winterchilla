<?php

namespace App;

global $router;

use function define;

/**
 * @file
 * List of API v0 endpoints
 * These endpoints may change as needed until v1 is released
 */
// Allowing all request methods lets us reply with HTTP 405 to unsupported methods at the controller level
$api_endpoint = function ($path, $controller) use ($router) {
  $router->map('POST|GET|PUT|DELETE', PUBLIC_API_V0_PATH.$path, $controller);
};
$api_endpoint('/appearances', 'API\AppearancesController#queryPublic');
$api_endpoint('/appearances/all', 'API\AppearancesController#queryAll');
$api_endpoint('/appearances/[i:id]/sprite', 'API\AppearancesController#sprite');
$api_endpoint('/appearances/[i:id]/color-groups', 'API\AppearancesController#getColorGroups');
$api_endpoint('/users/me', 'API\UsersController#me');
$api_endpoint('/about/server', 'API\AboutController#server');
$api_endpoint('/about/upcoming', 'AboutController#upcoming');
$api_endpoint('/admin/logs/details/[i:id]', 'AdminController#logDetail');
$api_endpoint('/admin/usefullinks/[i:id]?', 'AdminController#usefulLinksApi');
$api_endpoint('/admin/usefullinks/reorder', 'AdminController#reorderUsefulLinks');
$api_endpoint('/admin/notices/[i:id]?', 'AdminController#noticesApi');
$api_endpoint('/admin/stat-cache', 'AdminController#statCacheApi');
$api_endpoint('/cg/appearances', 'AppearanceController#autocomplete');
$api_endpoint('/cg/appearance/[i:id]?', 'AppearanceController#api');
$api_endpoint('/cg/appearance/[i:id]/colorgroups', 'AppearanceController#colorGroupsApi');
$api_endpoint('/cg/appearance/[i:id]/sprite', 'AppearanceController#spriteApi');
$api_endpoint('/cg/appearance/[i:id]/relations', 'AppearanceController#relationsApi');
$api_endpoint('/cg/appearance/[i:id]/cutiemarks', 'AppearanceController#cutiemarkApi');
$api_endpoint('/cg/appearance/[i:id]/tagged', 'AppearanceController#taggedApi');
$api_endpoint('/cg/appearance/[i:id]/template', 'AppearanceController#applyTemplate');
$api_endpoint('/cg/appearance/[i:id]/sanitize-svg', 'AppearanceController#sanitizeSvg');
$api_endpoint('/cg/appearance/[i:id]/selective', 'AppearanceController#selectiveClear');
$api_endpoint('/cg/appearance/[i:id]/guide-relations', 'AppearanceController#guideRelationsApi');
$api_endpoint('/cg/appearance/[i:id]/pin', 'AppearanceController#pinApi');
$api_endpoint('/cg/full/reorder', 'ColorGuideController#reorderFullList');
$api_endpoint('/cg/export', 'ColorGuideController#export');
$api_endpoint('/cg/reindex', 'ColorGuideController#reindex');
$api_endpoint('/cg/tags', 'TagController#autocomplete');
$api_endpoint('/cg/tags/recount-uses', 'TagController#recountUses');
$api_endpoint('/cg/tag/[i:id]?', 'TagController#api');
$api_endpoint('/cg/tag/[i:id]/synonym', 'TagController#synonymApi');
$api_endpoint('/cg/colorgroup/[i:id]?', 'ColorGroupController#api');
$api_endpoint('/da-auth/status', 'AuthController#sessionStatus');
$api_endpoint('/da-auth/sign-out', 'AuthController#signOut');
$api_endpoint('/show/[i:id]?', 'ShowController#api');
$api_endpoint('/show/[i:id]/posts', 'ShowController#postList');
$api_endpoint('/show/[i:id]/vote', 'ShowController#voteApi');
$api_endpoint('/show/[i:id]/guide-relations', 'ShowController#guideRelationsApi');
$api_endpoint('/show/next', 'ShowController#next');
$api_endpoint('/show/prefill', 'ShowController#prefill');
$api_endpoint('/event/[i:id]?', 'EventController#api');
$api_endpoint('/event/[i:id]/finalize', 'EventController#finalize');
$api_endpoint('/event/[i:id]/check-entries', 'EventController#checkEntries');
$api_endpoint('/event/[i:id]/entry', 'EventEntryController#api');
$api_endpoint('/event/entry/[i:entryid]', 'EventEntryController#api');
$api_endpoint('/event/entry/[i:entryid]/lazyload', 'EventEntryController#lazyload');
$api_endpoint('/notif', 'NotificationsController#get');
$api_endpoint('/notif/[i:id]/mark-read', 'NotificationsController#markRead');
$api_endpoint('/post/[i:id]?', 'PostController#api');
$api_endpoint('/post/[i:id]/lazyload', 'PostController#lazyload');
$api_endpoint('/post/[i:id]/finish', 'PostController#finishApi');
$api_endpoint('/post/[i:id]/locate', 'PostController#locate');
$api_endpoint('/post/[i:id]/reload', 'PostController#reload');
$api_endpoint('/post/[i:id]/unbreak', 'PostController#unbreak');
$api_endpoint('/post/[i:id]/approval', 'PostController#approvalApi');
$api_endpoint('/post/[i:id]/image', 'PostController#setImage');
$api_endpoint('/post/[i:id]/reservation', 'PostController#reservationApi');
$api_endpoint('/post/check-image', 'PostController#checkImage');
$api_endpoint('/post/reservation', 'PostController#addReservation');
$api_endpoint('/post/request/[i:id]', 'PostController#deleteRequest');
$api_endpoint('/post/request/suggestion', 'PostController#suggestRequest');
$api_endpoint('/setting/[au:key]', 'SettingController#api');
$api_endpoint('/user/session/[i:id]', 'UserController#sessionApi');
$api_endpoint('/user/password', 'UserController#passwordApi');
$api_endpoint('/user/verify', 'UserController#verifyApi');
$api_endpoint('/user/[i:id]/avatar-wrap', 'UserController#avatarWrap');
$api_endpoint('/user/[i:id]/contrib-cache', 'UserController#contribCacheApi');
$api_endpoint('/user/[i:id]/role', 'UserController#roleApi');
$api_endpoint('/user/[i:id]/email', 'UserController#emailApi');
$api_endpoint('/user/[i:id]/preference/[au:key]', 'PreferenceController#api');
$api_endpoint('/user/[i:id]/pcg/point-history/recalc', 'PersonalGuideController#pointRecalc');
$api_endpoint('/user/[i:id]/pcg/points', 'PersonalGuideController#pointsApi');
$api_endpoint('/user/[i:id]/pcg/slots', 'PersonalGuideController#slotsApi');

// "API" Endpoints
$router->map('POST', '/discord-connect/sync/[i:user_id]', 'DiscordAuthController#sync');
$router->map('POST', '/discord-connect/unlink/[i:user_id]', 'DiscordAuthController#unlink');
