<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App;

global $router;

/**
 * Allowing all request methods lets us reply with HTTP 405 to unsupported methods at the controller level
 *
 * @param string $path
 * @param array{0: class-string, 1: string} $target
 *
 * @return void
 */
$page_route = function ($path, array $target) use ($router) {
  $router->map('GET', $path, $target);
};
// Pages
# AboutController
$page_route('/about', [\App\Controllers\AboutController::class, 'index']);
$page_route('/about/browser/[i:session]?', [\App\Controllers\AboutController::class, 'browser']);
$page_route('/browser/[i:session]?', [\App\Controllers\AboutController::class, 'browser']);
$page_route('/about/privacy', [\App\Controllers\AboutController::class, 'privacy']);
# AdminController
$page_route('/admin', [\App\Controllers\AdminController::class, 'index']);
$page_route('/logs/[i]?', [\App\Controllers\AdminController::class, 'log']);
$page_route('/logs/[i]', [\App\Controllers\AdminController::class, 'log']);
$page_route('/admin/logs/[i]?', [\App\Controllers\AdminController::class, 'log']);
$page_route('/admin/discord', [\App\Controllers\AdminController::class, 'discord']);
$page_route('/admin/usefullinks', [\App\Controllers\AdminController::class, 'usefulLinks']);
$page_route('/admin/wsdiag', [\App\Controllers\AdminController::class, 'wsdiag']);
$page_route('/admin/pcg-appearances/[i]?', [\App\Controllers\AdminController::class, 'pcgAppearances']);
$page_route('/admin/notices', [\App\Controllers\AdminController::class, 'notices']);
# ColorGuideController
$page_route('/blending', [\App\Controllers\ColorGuideController::class, 'blending']);
$page_route('/[cg]/blending', [\App\Controllers\ColorGuideController::class, 'blending']);
$page_route('/[cg]/blending-reverse', [\App\Controllers\ColorGuideController::class, 'blendingReverse']);
$page_route('/[cg]/picker', [\App\Controllers\ColorGuideController::class, 'picker']);
$page_route('/[cg]/picker/frame', [\App\Controllers\ColorGuideController::class, 'pickerFrame']);
$page_route('/[cg]', [\App\Controllers\ColorGuideController::class, 'index']);
$page_route('/[cg]/preferred', [\App\Controllers\ColorGuideController::class, 'preferredGuide']);
$page_route('/[cg]/[guide:guide]?/[i]?', [\App\Controllers\ColorGuideController::class, 'guide']);
$page_route('/[cg]/[guide:guide]?/full', [\App\Controllers\ColorGuideController::class, 'fullList']);
$page_route('/[cg]/[guide:guide]?/changes/[i]?', [\App\Controllers\ColorGuideController::class, 'changeList']);
$page_route('/[cg]/[guide:guide]?/[v]', [\App\Controllers\ColorGuideController::class, 'guide']);
# AppearanceController
$page_route('/[cg]/[guide:guide]?/[v]/[i:id]-?', [\App\Controllers\AppearanceController::class, 'view']);
$page_route('/[cg]/[guide:guide]?/[v]/[i:id]-[adi]', [\App\Controllers\AppearanceController::class, 'view']);
$page_route('/[cg]/[guide:guide]?/[v]/[adi]-[i:id]', [\App\Controllers\AppearanceController::class, 'view']);
$page_route('/[cg]/[guide:guide]?/[v]/[i:id][cgimg:type]?.[cgext:ext]', [\App\Controllers\AppearanceController::class, 'asFile']);
$page_route('/[cg]/[guide:guide]?/tag-changes/[i:id][adi]?', [\App\Controllers\AppearanceController::class, 'tagChanges']);
$page_route('/users/[i:user_id]/[cg]/[guide:guide]?/[v]/[i:id](-[adi]?)', [\App\Controllers\AppearanceController::class, 'view']);
$page_route('/users/[i:user_id]/[cg]/[guide:guide]?/[v]/[adi]-[i:id]', [\App\Controllers\AppearanceController::class, 'view']);
$page_route('/users/[i:user_id]/[cg]/[guide:guide]?/[v]/[i:id][cgimg:type]?.[cgext:ext]', [\App\Controllers\AppearanceController::class, 'asFile']);
# ComponentsController
$page_route('/components', [\App\Controllers\ComponentsController::class, 'index']);
# DocsController
$page_route('/docs', [\App\Controllers\DocsController::class, 'index']);
# TagController
$page_route('/[cg]/[guide:guide]?/tags/[i]?', [\App\Controllers\TagController::class, 'list']);
# CutiemarkController
$page_route('/[cg]/cutiemark/[i:id].svg', [\App\Controllers\CutiemarkController::class, 'view']);
$page_route('/[cg]/cutiemark/download/[i:id][adi]?', [\App\Controllers\CutiemarkController::class, 'download']);
# AuthController
$page_route('/da-auth', [\App\Controllers\AuthController::class, 'softEnd']);
$page_route('/da-auth/begin', [\App\Controllers\AuthController::class, 'begin']);
$page_route('/da-auth/end', [\App\Controllers\AuthController::class, 'end']);
# DiscordAuthController
$page_route('/discord-connect/begin', [\App\Controllers\DiscordAuthController::class, 'begin']);
$page_route('/discord-connect/end', [\App\Controllers\DiscordAuthController::class, 'end']);
# ShowController
$page_route('/episode/[gen:gen]?/[epid:id]', [\App\Controllers\ShowController::class, 'viewEpisode']);
$page_route('/episode/[gen:gen]?/[epid:id]-?', [\App\Controllers\ShowController::class, 'viewEpisode']);
$page_route('/episode/[gen:gen]?/[epid:id]-[adi]?', [\App\Controllers\ShowController::class, 'viewEpisode']);
$page_route('/episode/latest', [\App\Controllers\ShowController::class, 'latest']);
$page_route('/episodes/[i]?', [\App\Controllers\ShowController::class, 'index']);
$page_route('/[st]/[i:id][adi]?', [\App\Controllers\ShowController::class, 'viewById']);
$page_route('/movies/[i]?', [\App\Controllers\ShowController::class, 'index']);
$page_route('/show', [\App\Controllers\ShowController::class, 'index']);
# EQGController
$page_route('/eqg/[i:id]', [\App\Controllers\EQGController::class, 'redirectInt']);
$page_route('/eqg/[adi:id]', [\App\Controllers\EQGController::class, 'redirectStr']);
# EventController
$page_route('/events/[i]?', [\App\Controllers\EventController::class, 'list']);
$page_route('/event/[i:id][adi]?', [\App\Controllers\EventController::class, 'view']);
# MuffinRatingController
$page_route('/muffin-rating', [\App\Controllers\MuffinRatingController::class, 'image']);
# PostController
$page_route('/s/[rr:thing]?/[ai:id]', [\App\Controllers\PostController::class, 'share']);
# UserController
$page_route('/', [\App\Controllers\UserController::class, 'homepage']);
$page_route('/users', [\App\Controllers\UserController::class, 'list']);
$page_route('/users/[i:user_id](-[uc]?)?', [\App\Controllers\UserController::class, 'profile']);
$page_route('/[sett]', [\App\Controllers\UserController::class, 'profile']);
$page_route('/u/[uuid:uuid]', [\App\Controllers\UserController::class, 'profileByUuid']);
$page_route('/users/[i:user_id]/contrib/[ad:type]/[i]?', [\App\Controllers\UserController::class, 'contrib']);
$page_route('/user/contrib/lazyload/[favme:favme]', [\App\Controllers\UserController::class, 'contribLazyload']);
$page_route('/users/[i:id]?/account', [\App\Controllers\UserController::class, 'account']);
$page_route('/users/verify', [\App\Controllers\UserController::class, 'verify']);
// Forced redirects from the old URL structure
$page_route('/@[un:name]', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/u/[un:name]?', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/contrib/[ad:type]/[i]?', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/[cg]/[guide:guide]?/[v]', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/[cg]/[i]?', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/[cg]/slot-history/[i]?', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/[cg]/point-history/[i]?', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/[cg]/[guide:guide]?/[v]/[i:id](-[adi]?)?', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/[cg]/[guide:guide]?/[v]/[adi]-[i:id]', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/[cg]/[guide:guide]?/[v]/[i:id][cgimg:type]?.[cgext:ext]', [\App\Controllers\UserController::class, 'forceRedirect']);
$page_route('/@[un:name]/[cg]/[guide:guide]?/sprite(-colors)?/[i:id][adi]?', [\App\Controllers\UserController::class, 'forceRedirect']);
# PersonalGuideController
$page_route('/users/[i:user_id]/[cg]/[guide:guide]?/[v]', [\App\Controllers\PersonalGuideController::class, 'list']);
$page_route('/users/[i:user_id]/[cg]/[i]?', [\App\Controllers\PersonalGuideController::class, 'list']);
$page_route('/users/[i:user_id]/[cg]/slot-history/[i]?', [\App\Controllers\PersonalGuideController::class, 'pointHistory']);
$page_route('/users/[i:user_id]/[cg]/point-history/[i]?', [\App\Controllers\PersonalGuideController::class, 'pointHistory']);
# ManifestController
$page_route('/manifest', [\App\Controllers\ManifestController::class, 'json']);
# DiagnoseController
$page_route('/diagnose/ex/[a:type]', [\App\Controllers\DiagnoseController::class, 'exception']);
$page_route('/diagnose/lt/[i:time]', [\App\Controllers\DiagnoseController::class, 'loadtime']);
# TestController — only registered in TEST_MODE
if (\App\CoreUtils::env('TEST_MODE')) {
  $page_route('/test-login/[i:user_id]', [\App\Controllers\TestController::class, 'loginAs']);
  $page_route('/test-dialog', [\App\Controllers\TestController::class, 'dialogPage']);
}
