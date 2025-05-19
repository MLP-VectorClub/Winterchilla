<?php

namespace App;

global $router;

// Proper REST API endpoints (sort of)
// Allowing all request methods lets us reply with HTTP 405 to unsupported methods at the controller level
$private_api_endpoint = function ($path, $controller) use ($router) {
  $router->map('POST|GET|PUT|DELETE', PRIVATE_API_PATH.$path, $controller);
};
$private_api_endpoint('/about/upcoming', 'AboutController#upcoming');
$private_api_endpoint('/admin/logs/details/[i:id]', 'AdminController#logDetail');
$private_api_endpoint('/admin/usefullinks/[i:id]?', 'AdminController#usefulLinksApi');
$private_api_endpoint('/admin/usefullinks/reorder', 'AdminController#reorderUsefulLinks');
$private_api_endpoint('/admin/wsdiag/hello', 'AdminController#wshello');
$private_api_endpoint('/admin/notices/[i:id]?', 'AdminController#noticesApi');
$private_api_endpoint('/admin/stat-cache', 'AdminController#statCacheApi');
$private_api_endpoint('/cg/appearances', 'AppearanceController#autocomplete');
$private_api_endpoint('/cg/appearance/[i:id]?', 'AppearanceController#api');
$private_api_endpoint('/cg/appearance/[i:id]/colorgroups', 'AppearanceController#colorGroupsApi');
$private_api_endpoint('/cg/appearance/[i:id]/sprite', 'AppearanceController#spriteApi');
$private_api_endpoint('/cg/appearance/[i:id]/relations', 'AppearanceController#relationsApi');
$private_api_endpoint('/cg/appearance/[i:id]/cutiemarks', 'AppearanceController#cutiemarkApi');
$private_api_endpoint('/cg/appearance/[i:id]/tagged', 'AppearanceController#taggedApi');
$private_api_endpoint('/cg/appearance/[i:id]/template', 'AppearanceController#applyTemplate');
$private_api_endpoint('/cg/appearance/[i:id]/sanitize-svg', 'AppearanceController#sanitizeSvg');
$private_api_endpoint('/cg/appearance/[i:id]/selective', 'AppearanceController#selectiveClear');
$private_api_endpoint('/cg/appearance/[i:id]/guide-relations', 'AppearanceController#guideRelationsApi');
$private_api_endpoint('/cg/appearance/[i:id]/pin', 'AppearanceController#pinApi');
$private_api_endpoint('/cg/full/reorder', 'ColorGuideController#reorderFullList');
$private_api_endpoint('/cg/export', 'ColorGuideController#export');
$private_api_endpoint('/cg/reindex', 'ColorGuideController#reindex');
$private_api_endpoint('/cg/tags', 'TagController#autocomplete');
$private_api_endpoint('/cg/tags/recount-uses', 'TagController#recountUses');
$private_api_endpoint('/cg/tag/[i:id]?', 'TagController#api');
$private_api_endpoint('/cg/tag/[i:id]/synonym', 'TagController#synonymApi');
$private_api_endpoint('/cg/colorgroup/[i:id]?', 'ColorGroupController#api');
$private_api_endpoint('/da-auth/status', 'AuthController#sessionStatus');
$private_api_endpoint('/da-auth/sign-out', 'AuthController#signOut');
$private_api_endpoint('/show/[i:id]?', 'ShowController#api');
$private_api_endpoint('/show/[i:id]/posts', 'ShowController#postList');
$private_api_endpoint('/show/[i:id]/vote', 'ShowController#voteApi');
$private_api_endpoint('/show/[i:id]/guide-relations', 'ShowController#guideRelationsApi');
$private_api_endpoint('/show/next', 'ShowController#next');
$private_api_endpoint('/show/prefill', 'ShowController#prefill');
$private_api_endpoint('/event/[i:id]?', 'EventController#api');
$private_api_endpoint('/event/[i:id]/finalize', 'EventController#finalize');
$private_api_endpoint('/event/[i:id]/check-entries', 'EventController#checkEntries');
$private_api_endpoint('/event/[i:id]/entry', 'EventEntryController#api');
$private_api_endpoint('/event/entry/[i:entryid]', 'EventEntryController#api');
$private_api_endpoint('/event/entry/[i:entryid]/lazyload', 'EventEntryController#lazyload');
$private_api_endpoint('/notif', 'NotificationsController#get');
$private_api_endpoint('/notif/[i:id]/mark-read', 'NotificationsController#markRead');
$private_api_endpoint('/post/[i:id]?', 'PostController#api');
$private_api_endpoint('/post/[i:id]/lazyload', 'PostController#lazyload');
$private_api_endpoint('/post/[i:id]/finish', 'PostController#finishApi');
$private_api_endpoint('/post/[i:id]/locate', 'PostController#locate');
$private_api_endpoint('/post/[i:id]/reload', 'PostController#reload');
$private_api_endpoint('/post/[i:id]/unbreak', 'PostController#unbreak');
$private_api_endpoint('/post/[i:id]/approval', 'PostController#approvalApi');
$private_api_endpoint('/post/[i:id]/image', 'PostController#setImage');
$private_api_endpoint('/post/[i:id]/reservation', 'PostController#reservationApi');
$private_api_endpoint('/post/check-image', 'PostController#checkImage');
$private_api_endpoint('/post/reservation', 'PostController#addReservation');
$private_api_endpoint('/post/request/[i:id]', 'PostController#deleteRequest');
$private_api_endpoint('/post/request/suggestion', 'PostController#suggestRequest');
$private_api_endpoint('/setting/[au:key]', 'SettingController#api');
$private_api_endpoint('/user/session/[i:id]', 'UserController#sessionApi');
$private_api_endpoint('/user/password', 'UserController#passwordApi');
$private_api_endpoint('/user/verify', 'UserController#verifyApi');
$private_api_endpoint('/user/[i:id]/avatar-wrap', 'UserController#avatarWrap');
$private_api_endpoint('/user/[i:id]/contrib-cache', 'UserController#contribCacheApi');
$private_api_endpoint('/user/[i:id]/role', 'UserController#roleApi');
$private_api_endpoint('/user/[i:id]/email', 'UserController#emailApi');
$private_api_endpoint('/user/[i:id]/preference/[au:key]', 'PreferenceController#api');
$private_api_endpoint('/user/[i:id]/pcg/point-history/recalc', 'PersonalGuideController#pointRecalc');
$private_api_endpoint('/user/[i:id]/pcg/points', 'PersonalGuideController#pointsApi');
$private_api_endpoint('/user/[i:id]/pcg/slots', 'PersonalGuideController#slotsApi');

// "API" Endpoints
$router->map('POST', '/discord-connect/sync/[i:user_id]', 'DiscordAuthController#sync');
$router->map('POST', '/discord-connect/unlink/[i:user_id]', 'DiscordAuthController#unlink');
