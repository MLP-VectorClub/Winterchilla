<?php

/**
 * Features that only exist on the new site (Celestia). Winterchilla answers their addresses with a page that says so and links to the new site,
 * instead of a 404. To add one, add an entry here, nothing else is needed (the routes are registered from this list in `routes/pages.php`):
 *
 *  - 'title'    What the page is called ("{title} is only available on the new site")
 *  - 'summary'  One or two sentences about what it does, shown under the title
 *  - 'routes'   Route patterns of this site that the feature has (or would have had), in AltoRouter syntax like the ones in `routes/pages.php`
 *  - 'new_path' Where the feature is on the new site, `{name}` is replaced with the route parameter of that name. When left out the page is
 *               expected at the same path as it was requested at
 *
 * The link goes to `READ_ONLY_URL` (the address of the new site, see CLAUDE.md); without it the page only explains.
 */
return [
  'sprite-colors' => [
    'title' => 'The sprite color check',
    'summary' => 'Shows every color used in an appearance\'s sprite next to the colors listed for it, and points out colors that are missing, slightly off or not listed at all. It is also handy for checking custom uploads.',
    'routes' => [
      '/[cg]/[guide:guide]?/sprite-colors/[i:id]',
      '/users/[i:user_id]/[cg]/sprite-colors/[i:id]',
    ],
    'new_path' => '/cg/sprite-colors/{id}',
  ],
];
