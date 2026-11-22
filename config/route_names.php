<?php

/**
 * The route names Celestia uses (its Next.js page files, e.g. `/cg/[guide]/full`) for the pages of this site that also exist there. Celestia is
 * authoritative: when a page exists in both, the name here is Celestia's, so the visitor statistics read the same for both sites (see `App\RouteNames`
 * and docs on the websocket `navigate` event). Keys are the route patterns of `routes/pages.php`, exactly as written there.
 *
 * Pages that have no counterpart (redirects, files, images, API-ish endpoints, anything only this site has) are left out: they are reported as unknown,
 * which keeps real addresses, with their ids and names, away from the statistics. `{first}` stands for the first segment of the requested address (for
 * routes where that segment is the page type, like `/movie/12` or `/u`, and is one of a fixed set the route itself matched).
 */
return [
  '/' => '/',
  '/about' => '/about',
  '/about/browser/[i:session]?' => '/about/connection',
  '/browser/[i:session]?' => '/about/connection',
  '/about/privacy' => '/about/privacy',
  '/admin' => '/admin',
  '/logs/[i]?' => '/admin/logs',
  '/logs/[i]' => '/admin/logs',
  '/admin/logs/[i]?' => '/admin/logs',
  '/admin/usefullinks' => '/admin/usefullinks',
  '/admin/wsdiag' => '/admin/wsdiag',
  '/admin/pcg-appearances/[i]?' => '/admin/pcg-appearances',
  '/admin/notices' => '/admin/notices',
  '/blending' => '/cg/blending',
  '/[cg]/blending' => '/cg/blending',
  '/[cg]/blending-reverse' => '/cg/blending-reverse',
  '/[cg]/picker' => '/cg/picker',
  '/[cg]' => '/cg',
  '/[cg]/[guide:guide]?/[i]?' => '/cg/[guide]',
  '/[cg]/[guide:guide]?/full' => '/cg/[guide]/full',
  '/[cg]/[guide:guide]?/changes/[i]?' => '/cg/[guide]/changes',
  '/[cg]/[guide:guide]?/[v]/[i:id]-?' => '/cg/[guide]/v/[id]',
  '/[cg]/[guide:guide]?/[v]/[i:id]-[adi]' => '/cg/[guide]/v/[id]',
  '/[cg]/[guide:guide]?/[v]/[adi]-[i:id]' => '/cg/[guide]/v/[id]',
  '/users/[i:user_id]/[cg]/[guide:guide]?/[v]/[i:id](-[adi]?)' => '/users/[user]/cg/v/[id]',
  '/users/[i:user_id]/[cg]/[guide:guide]?/[v]/[adi]-[i:id]' => '/users/[user]/cg/v/[id]',
  '/[cg]/[guide:guide]?/tags/[i]?' => '/cg/[guide]/tags',
  '/components' => '/components',
  '/episode/[gen:gen]?/[epid:id]' => '/episode/[id]',
  '/episode/[gen:gen]?/[epid:id]-?' => '/episode/[id]',
  '/episode/[gen:gen]?/[epid:id]-[adi]?' => '/episode/[id]',
  '/episode/latest' => '/episode/[id]',
  '/episodes/[i]?' => '/episodes/[[...page]]',
  '/[st]/[i:id][adi]?' => '/{first}/[id]',
  '/movies/[i]?' => '/movies/[[...page]]',
  '/show' => '/show',
  '/events/[i]?' => '/events',
  '/event/[i:id][adi]?' => '/event/[id]',
  '/muffin-rating' => '/muffin-rating',
  '/users' => '/users',
  '/users/[i:user_id](-[uc]?)?' => '/users/[user]',
  '/[sett]' => '/{first}',
  '/u/[uuid:uuid]' => '/u/[name]',
  '/users/[i:user_id]/contrib/[ad:type]/[i]?' => '/users/[user]/contrib/[type]',
  '/users/[i:id]?/account' => '/users/[user]/account',
  '/users/verify' => '/users/verify',
  '/users/[i:user_id]/[cg]/[guide:guide]?/[v]' => '/users/[user]/cg',
  '/users/[i:user_id]/[cg]/[i]?' => '/users/[user]/cg',
  '/users/[i:user_id]/[cg]/slot-history/[i]?' => '/users/[user]/cg/point-history',
  '/users/[i:user_id]/[cg]/point-history/[i]?' => '/users/[user]/cg/point-history',
];
