<?php

use Phinx\Seed\AbstractSeed;

class TestSeeder extends AbstractSeed {
  public function getDependencies(): array {
    return ['GlobalSettingSeeder'];
  }

  public function run(): void {
    // Users
    $this->table('users')->insert([
      ['id' => 9001, 'name' => 'TestUser',  'role' => 'user',  'created_at' => date('c'), 'updated_at' => date('c')],
      ['id' => 9002, 'name' => 'TestAdmin', 'role' => 'admin', 'created_at' => date('c'), 'updated_at' => date('c')],
      // Has done nothing yet: no personal appearances, points or preferences, so it has the defaults every new user gets
      // (the free slot, i.e. 10 points, and permission to create personal appearances)
      ['id' => 9003, 'name' => 'FreshUser', 'role' => 'user', 'created_at' => date('c'), 'updated_at' => date('c')],
      // Discord members for the sync/unlink API tests: 9004 was synced just now (cooldown), 9005 is linked and gets unlinked,
      // 9006 is known to the site but has not linked the account (no access token)
      ['id' => 9004, 'name' => 'DiscordSynced', 'role' => 'user', 'created_at' => date('c'), 'updated_at' => date('c')],
      ['id' => 9005, 'name' => 'DiscordLinked', 'role' => 'user', 'created_at' => date('c'), 'updated_at' => date('c')],
      ['id' => 9006, 'name' => 'DiscordUnlinked', 'role' => 'user', 'created_at' => date('c'), 'updated_at' => date('c')],
      // The one developer, for the developer-only endpoints
      ['id' => 9007, 'name' => 'TestDeveloper', 'role' => 'developer', 'created_at' => date('c'), 'updated_at' => date('c')],
    ])->save();

    $this->table('discord_members')->insert([
      ['id' => 900000000000009004, 'user_id' => 9004, 'username' => 'discordsynced', 'discriminator' => 0, 'access' => 'fake-discord-access-a', 'refresh' => 'fake-discord-refresh-a', 'scope' => 'identify', 'expires' => date('c', strtotime('+10 years')), 'last_synced' => date('c')],
      ['id' => 900000000000009005, 'user_id' => 9005, 'username' => 'discordlinked', 'discriminator' => 0, 'access' => 'fake-discord-access-b', 'refresh' => 'fake-discord-refresh-b', 'scope' => 'identify', 'expires' => date('c', strtotime('+10 years')), 'last_synced' => null],
      ['id' => 900000000000009006, 'user_id' => 9006, 'username' => 'discordunlinked', 'discriminator' => 0, 'access' => null, 'refresh' => null, 'scope' => null, 'expires' => null, 'last_synced' => null],
    ])->save();

    // DeviantArt linked accounts (access_expires far in the future to avoid token refresh)
    $this->table('deviantart_users')->insert([
      [
        // Fixed IDs (TestSeederConstants::*_DA_ID) so the fake OAuth provider can sign in as these users
        'id'             => '0f0e0d0c-0b0a-4000-8000-000000009001',
        'name'           => 'TestUser',
        'avatar_url'     => '/img/blank-pixel.png',
        'user_id'        => 9001,
        'scope'          => 'user',
        'access'         => 'fake-access-token-user',
        'refresh'        => 'fake-refresh-token-user',
        'access_expires' => date('c', strtotime('+10 years')),
        'created_at'     => date('c'),
      ],
      [
        'id'             => '0f0e0d0c-0b0a-4000-8000-000000009002',
        'name'           => 'TestAdmin',
        'avatar_url'     => '/img/blank-pixel.png',
        'user_id'        => 9002,
        'scope'          => 'user',
        'access'         => 'fake-access-token-admin',
        'refresh'        => 'fake-refresh-token-admin',
        'access_expires' => date('c', strtotime('+10 years')),
        'created_at'     => date('c'),
      ],
      [
        'id'             => '0f0e0d0c-0b0a-4000-8000-000000009007',
        'name'           => 'TestDeveloper',
        'avatar_url'     => '/img/blank-pixel.png',
        'user_id'        => 9007,
        'scope'          => 'user',
        'access'         => 'fake-access-token-developer',
        'refresh'        => 'fake-refresh-token-developer',
        'access_expires' => date('c', strtotime('+10 years')),
        'created_at'     => date('c'),
      ],
      [
        'id'             => '0f0e0d0c-0b0a-4000-8000-000000009003',
        'name'           => 'FreshUser',
        'avatar_url'     => '/img/blank-pixel.png',
        'user_id'        => 9003,
        'scope'          => 'user',
        'access'         => 'fake-access-token-fresh',
        'refresh'        => 'fake-refresh-token-fresh',
        'access_expires' => date('c', strtotime('+10 years')),
        'created_at'     => date('c'),
      ],
    ])->save();

    // Show entries: an episode (S01E01) and a movie
    $this->table('show')->insert([[
      'id'        => 1,
      'type'      => 'episode',
      'season'    => 1,
      'episode'   => 1,
      'parts'     => 1,
      'title'     => 'Friendship is Magic, Part 1',
      'airs'      => '2010-10-10 00:00:00+00',
      'no'        => 1,
      'posted_by' => 9002,
      'notes'     => null,
    ], [
      // A non-episode show entry (movie); must match TestSeederConstants::MOVIE_ID
      'id'        => 2,
      'type'      => 'movie',
      'season'    => null,
      'episode'   => null,
      'parts'     => 1,
      'title'     => 'Equestria Girls',
      'airs'      => '2013-06-16 00:00:00+00',
      'no'        => 1,
      'posted_by' => 9002,
      'notes'     => null,
    ]])->save();

    // A request on the episode; must match TestSeederConstants::POST_ID
    $this->table('posts')->insert([[
      'id'           => 1,
      'type'         => 'chr',
      'preview'      => '/img/blank-pixel.png',
      'fullsize'     => '/img/blank-pixel.png',
      'label'        => 'Seeded Test Request',
      'requested_at' => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
    ], [
      // Requests for the API tests: 2 is deleted by its owner, 3 is already reserved (so its owner can't delete it)
      'id'           => 2,
      'type'         => 'chr',
      'preview'      => '/img/blank-pixel.png',
      'fullsize'     => '/img/blank-pixel.png',
      'label'        => 'Deletable Test Request',
      'requested_at' => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
    ], [
      'id'           => 3,
      'type'         => 'obj',
      'preview'      => '/img/blank-pixel.png',
      'fullsize'     => '/img/blank-pixel.png',
      'label'        => 'Reserved Test Request',
      'requested_at' => date('c'),
      'reserved_at'  => date('c'),
      'deviation_id' => 'dfin001',
      'finished_at'  => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
      'reserved_by'  => 9002,
    ], [
      // A broken request with usable images, for the unbreak test
      'id'           => 4,
      'type'         => 'bg',
      'preview'      => '/img/blank-pixel.png',
      'fullsize'     => '/img/blank-pixel.png',
      'label'        => 'Broken Test Request',
      'requested_at' => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
      'broken'       => true,
    ], [
      // Like 4, but cleared through the UI test
      'id'           => 5,
      'type'         => 'obj',
      'preview'      => '/img/blank-pixel.png',
      'fullsize'     => '/img/blank-pixel.png',
      'label'        => 'Broken UI Request',
      'requested_at' => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
      'broken'       => true,
    ]])->save();

    // Notifications about the seeded post: two unread ones for the regular user (the API tests consume one) and
    // one for the admin (to check that users can't touch each other's). IDs must match TestSeederConstants.
    $notification_data = json_encode(['id' => 1, 'type' => 'request']);
    $this->table('notifications')->insert([
      ['id' => 1, 'type' => 'post-approved', 'data' => $notification_data, 'recipient_id' => 9001],
      ['id' => 2, 'type' => 'post-approved', 'data' => $notification_data, 'recipient_id' => 9001],
      ['id' => 3, 'type' => 'post-approved', 'data' => $notification_data, 'recipient_id' => 9002],
    ])->save();

    // An appearance in the pony color guide
    $this->table('appearances')->insert([[
      'id'          => 1,
      'order'       => 1,
      'label'       => 'Twilight Sparkle',
      'notes_src'   => null,
      'notes_rend'  => null,
      'owner_id'    => null,
      'guide'       => 'pony',
      'private'     => false,
      'sprite_hash' => null,
      'created_at'  => date('c'),
      'updated_at'  => date('c'),
      'last_cleared' => null,
    ]])->save();

    // Personal guide of the regular user: a public appearance with a color group (PERSONAL_APPEARANCE_ID) and a
    // private one (PRIVATE_PERSONAL_APPEARANCE_ID). Slot history is recalculated from these on first view.
    $personal = ['order' => null, 'notes_src' => null, 'notes_rend' => null, 'owner_id' => 9001, 'guide' => null, 'sprite_hash' => null,
      'created_at' => date('c'), 'updated_at' => date('c'), 'last_cleared' => null];
    $this->table('appearances')->insert([
      $personal + ['id' => 3, 'label' => 'Personal Test Pony', 'private' => false],
      $personal + ['id' => 4, 'label' => 'Private Test Pony', 'private' => true, 'token' => '0f0e0d0c-0b0a-4000-8000-00000000f004'],
    ])->save();
    $this->table('color_groups')->insert([
      ['id' => 1, 'appearance_id' => 3, 'label' => 'Personal Coat', 'order' => 1],
    ])->save();
    $this->table('colors')->insert([
      ['group_id' => 1, 'order' => 1, 'label' => 'Personal Base', 'hex' => '#AA55CC'],
    ])->save();
    // Manual point grant from the admin to the regular user
    $this->table('pcg_point_grants')->insert([[
      'amount' => 5, 'comment' => 'Seeded contract test grant', 'receiver_id' => 9001, 'sender_id' => 9002, 'created_at' => date('c'),
    ]])->save();
    // Major changes on the official appearance; the newest first on the changes list
    $this->table('major_changes')->insert([
      ['appearance_id' => 1, 'reason' => 'Seeded older major change', 'user_id' => 9002, 'created_at' => date('c', strtotime('-2 days')), 'updated_at' => date('c', strtotime('-2 days'))],
      ['appearance_id' => 1, 'reason' => 'Seeded newest major change', 'user_id' => 9002, 'created_at' => date('c', strtotime('-1 day')), 'updated_at' => date('c', strtotime('-1 day'))],
    ])->save();

    // A cutie mark for the appearance above, backed by a source SVG fixture on disk.
    // ID must match TestSeederConstants::CUTIEMARK_ID (kept high since fs/ is shared with dev).
    $cutiemark_id = 900001;
    $this->table('cutiemarks')->insert([[
      'id'             => $cutiemark_id,
      'appearance_id'  => 1,
      'facing'         => 'left',
      'favme'          => null,
      'rotation'       => 0,
      'contributor_id' => null,
      'label'          => null,
    ]])->save();
    $fs = dirname(__DIR__, 2).'/fs/';
    if (!is_dir($fs.'cm_source'))
      mkdir($fs.'cm_source', 0777, true);
    copy(dirname(__DIR__, 2).'/tests/Browser/fixtures/cutiemark.svg', $fs."cm_source/$cutiemark_id.svg");
    // Drop derived files from any previous run so they get regenerated from the fixture
    foreach (["cm_tokenized/$cutiemark_id.svg", "cg_render/cutiemark/$cutiemark_id.svg"] as $derived)
      if (file_exists($fs.$derived))
        unlink($fs.$derived);

    // A second appearance with a cutie mark and all of its files, for the appearance deletion test (the files have to be
    // removed along with the appearance). IDs must match TestSeederConstants::DELETABLE_*.
    $deletable_cutiemark_id = 900002;
    $this->table('appearances')->insert([[
      'id'          => 2,
      'order'       => 2,
      'label'       => 'Deletable Test Pony',
      'owner_id'    => null,
      'guide'       => 'pony',
      'private'     => false,
      'created_at'  => date('c'),
      'updated_at'  => date('c'),
    ]])->save();
    $this->table('cutiemarks')->insert([[
      'id'             => $deletable_cutiemark_id,
      'appearance_id'  => 2,
      'facing'         => 'left',
      'rotation'       => 0,
    ]])->save();
    foreach (['cm_source', 'cm_tokenized', 'cg_render/cutiemark'] as $folder) {
      if (!is_dir($fs.$folder))
        mkdir($fs.$folder, 0777, true);
      copy(dirname(__DIR__, 2).'/tests/Browser/fixtures/cutiemark.svg', $fs."$folder/$deletable_cutiemark_id.svg");
    }

    // An event
    $this->table('events')->insert([[
      'id'          => 1,
      'name'        => 'Test Coloring Event',
      'entry_role'  => 'user',
      'vote_role'   => null,
      'starts_at'   => date('c', strtotime('-1 day')),
      'ends_at'     => date('c', strtotime('+7 days')),
      'added_by'    => 9002,
      'desc_src'    => 'Test event description.',
      'desc_rend'   => '<p>Test event description.</p>',
      'max_entries' => null,
      'result_favme' => null,
      'finalized_by' => null,
      'finalized_at' => null,
      'created_at'  => date('c'),
      'updated_at'  => date('c'),
    ]])->save();

    // Event entries: 1 is the regular user's (read/edit tests), 2 the admin's (ownership tests), 3 the regular
    // user's and only used for deletion. IDs must match TestSeederConstants::EVENT_ENTRY_*.
    $entry = ['event_id' => 1, 'sub_prov' => 'fav.me', 'prev_src' => null, 'prev_full' => null, 'prev_thumb' => null, 'created_at' => $now = date('c'), 'updated_at' => $now];
    $this->table('event_entries')->insert([
      $entry + ['id' => 1, 'sub_id' => 'd1b2c3d', 'title' => 'Seeded Entry', 'submitted_by' => 9001],
      $entry + ['id' => 2, 'sub_id' => 'd1b2c3e', 'title' => 'Admin Entry', 'submitted_by' => 9002],
      $entry + ['id' => 3, 'sub_id' => 'd1b2c3f', 'title' => 'Doomed Entry', 'submitted_by' => 9001],
      $entry + ['id' => 4, 'sub_id' => 'd1b2c3g', 'title' => 'Withdrawn Entry', 'submitted_by' => 9001],
    ])->save();

    // Deviation metadata lives in Redis; without these the event page would ask the real DeviantArt oEmbed API
    // about our made-up submission IDs (slow, and a network dependency in tests)
    foreach (['d1b2c3d' => 'Seeded Entry', 'd1b2c3e' => 'Admin Entry', 'd1b2c3f' => 'Doomed Entry', 'd1b2c3g' => 'Withdrawn Entry', 'dfin001' => 'Finished Test Vector', 'dfin002' => 'Vector For Finishing', 'dfin003' => 'Vector For The UI Test', 'dfin004' => 'Vector By Someone Else', 'dfin005' => 'Image For New Posts', 'dfin006' => 'Image For Changing', 'dfin007' => 'Image For The UI Test', 'dfin008' => 'Vector Awaiting Approval', 'dfin009' => 'Approved Vector'] as $sub_id => $title)
      \App\Models\CachedDeviation::create([
        'provider' => 'fav.me',
        'id' => $sub_id,
        'title' => $title,
        'author' => in_array($sub_id, ['dfin002', 'dfin003'], true) ? 'TestAdmin' : (in_array($sub_id, ['dfin008', 'dfin009'], true) ? 'TestMember' : 'TestUser'),
        // Made-up deviations point at an image the test server serves, so nothing has to leave the machine; the
        // query string keeps the URLs unique (posts refuse an image that another post already uses)
        'preview' => "/img/blank-pixel.png?d=$sub_id",
        'fullsize' => "/img/blank-pixel.png?d=$sub_id",
        'type' => 'png',
      ]);

    // A log entry with structured data, for the admin log API tests (TestSeederConstants::LOG_ID)
    $this->table('logs')->insert([[
      'id' => 1, 'initiator' => 9002, 'entry_type' => 'rolechange', 'ip' => '127.0.0.1',
      'data' => json_encode(['target' => 9001, 'oldrole' => 'user', 'newrole' => 'member']), 'created_at' => date('c'),
    ]])->save();

    $this->seedEveryState();

    // Rows with explicit IDs don't advance their sequences, so the next row created by the app would collide
    foreach (['appearances', 'color_groups', 'cutiemarks', 'show', 'posts', 'notifications', 'events', 'event_entries', 'logs', 'tags', 'notices', 'useful_links', 'sessions', 'locked_posts'] as $table)
      $this->execute("SELECT setval(pg_get_serial_sequence('$table', 'id'), GREATEST((SELECT MAX(id) FROM $table), ".($table === 'appearances' ? 900100 : 1)."))");
  }
  /**
   * Every state the UI has something to show for, so the browser tests and the visual audits find it in the seed instead of building it first:
   * roles below admin, tags of every kind (typed, aliased, stale use count), colors, reservations in every state, votes, notices, useful links,
   * sessions and a second and a third show entry. IDs must match TestSeederConstants.
   */
  private function seedEveryState(): void {
    $now = date('c');
    $ago = fn(string $when) => date('c', strtotime($when));

    // A club member and an assistant (the roles between a regular user and an admin)
    $this->table('users')->insert([
      ['id' => 9008, 'name' => 'TestMember', 'role' => 'member', 'created_at' => $now, 'updated_at' => $now],
      ['id' => 9009, 'name' => 'TestAssistant', 'role' => 'assistant', 'created_at' => $now, 'updated_at' => $now],
    ])->save();
    $this->table('deviantart_users')->insert([
      ['id' => '0f0e0d0c-0b0a-4000-8000-000000009008', 'name' => 'TestMember', 'avatar_url' => '/img/blank-pixel.png', 'user_id' => 9008, 'scope' => 'user',
        'access' => 'fake-access-token-member', 'refresh' => 'fake-refresh-token-member', 'access_expires' => $ago('+10 years'), 'created_at' => $now],
      ['id' => '0f0e0d0c-0b0a-4000-8000-000000009009', 'name' => 'TestAssistant', 'avatar_url' => '/img/blank-pixel.png', 'user_id' => 9009, 'scope' => 'user',
        'access' => 'fake-access-token-assistant', 'refresh' => 'fake-refresh-token-assistant', 'access_expires' => $ago('+10 years'), 'created_at' => $now],
    ])->save();

    // More of the guide: a second pony, and an Equestria Girls character, so the guide has a list, pages and both guides
    $official = ['notes_src' => null, 'notes_rend' => null, 'owner_id' => null, 'private' => false, 'sprite_hash' => null,
      'created_at' => $now, 'updated_at' => $now, 'last_cleared' => null];
    $this->table('appearances')->insert([
      array_merge($official, ['id' => 5, 'order' => 2, 'label' => 'Rainbow Dash', 'guide' => 'pony', 'notes_src' => 'Fastest flyer in *Ponyville*', 'notes_rend' => 'Fastest flyer in <em>Ponyville</em>']),
      $official + ['id' => 6, 'order' => 3, 'label' => 'Fluttershy', 'guide' => 'pony'],
      $official + ['id' => 7, 'order' => 1, 'label' => 'Sunset Shimmer', 'guide' => 'eqg'],
    ])->save();
    $this->table('color_groups')->insert([
      // (Appearance 1 stays without groups: the color group tests count on that)
      ['id' => 2, 'appearance_id' => 5, 'label' => 'Coat', 'order' => 1],
      ['id' => 3, 'appearance_id' => 5, 'label' => 'Mane', 'order' => 2],
      ['id' => 4, 'appearance_id' => 6, 'label' => 'Coat', 'order' => 1],
      ['id' => 5, 'appearance_id' => 7, 'label' => 'Skin', 'order' => 1],
    ])->save();
    $this->table('colors')->insert([
      ['group_id' => 2, 'order' => 1, 'label' => 'Fill', 'hex' => '#D19FE4'],
      ['group_id' => 2, 'order' => 2, 'label' => 'Outline', 'hex' => '#A06BBE'],
      ['group_id' => 3, 'order' => 1, 'label' => 'Base', 'hex' => '#273771'],
      ['group_id' => 3, 'order' => 2, 'label' => 'Stripe', 'hex' => '#EB5191'],
      ['group_id' => 4, 'order' => 1, 'label' => 'Fill', 'hex' => '#9BDBF5'],
      ['group_id' => 5, 'order' => 1, 'label' => 'Fill', 'hex' => '#F3B5CF'],
    ])->save();

    // Tags: one of every type, an alias (a synonym of Twilight Sparkle), one nothing uses, and one whose stored use count is stale (the
    // "refresh use count" button has something to fix)
    $this->table('tags')->insert([
      ['id' => 1, 'name' => 'twilight sparkle', 'title' => 'The main character', 'type' => 'char', 'uses' => 1, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 2, 'name' => 'rainbow dash', 'title' => null, 'type' => 'char', 'uses' => 1, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 3, 'name' => 'unicorn', 'title' => null, 'type' => 'spec', 'uses' => 1, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 4, 'name' => 'pegasus', 'title' => null, 'type' => 'spec', 'uses' => 2, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 5, 'name' => 'female', 'title' => null, 'type' => 'gen', 'uses' => 3, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 6, 'name' => 'saddlebags', 'title' => null, 'type' => 'app', 'uses' => 0, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 7, 'name' => 'mane six', 'title' => 'The six main characters', 'type' => 'cat', 'uses' => 2, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 8, 'name' => 'nightmare', 'title' => null, 'type' => 'warn', 'uses' => 0, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 9, 'name' => 'untyped tag', 'title' => null, 'type' => null, 'uses' => 0, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 10, 'name' => 'twily', 'title' => null, 'type' => null, 'uses' => 0, 'synonym_of' => 1, 'created_at' => $now, 'updated_at' => $now],
      ['id' => 11, 'name' => 'stale count', 'title' => null, 'type' => null, 'uses' => 7, 'synonym_of' => null, 'created_at' => $now, 'updated_at' => $now],
    ])->save();
    $this->table('tagged')->insert([
      ['tag_id' => 1, 'appearance_id' => 1], ['tag_id' => 3, 'appearance_id' => 1], ['tag_id' => 5, 'appearance_id' => 1], ['tag_id' => 7, 'appearance_id' => 1],
      ['tag_id' => 2, 'appearance_id' => 5], ['tag_id' => 4, 'appearance_id' => 5], ['tag_id' => 5, 'appearance_id' => 5], ['tag_id' => 7, 'appearance_id' => 5],
      ['tag_id' => 4, 'appearance_id' => 6], ['tag_id' => 5, 'appearance_id' => 6],
    ])->save();
    $this->table('related_appearances')->insert([['source_id' => 1, 'target_id' => 5], ['source_id' => 5, 'target_id' => 1]])->save();
    $this->table('pinned_appearances')->insert([['appearance_id' => 5, 'guide' => 'pony', 'order' => 1, 'created_at' => $now, 'updated_at' => $now]])->save();

    // Show entries: a later episode that aired (with votes and linked appearances, other than appearance 1 whose tests link it themselves), and one that has not aired yet (the sidebar's "happening soon").
    // Season 2, since the show tests create episodes of season 1
    $this->table('show')->insert([
      ['id' => 3, 'type' => 'episode', 'season' => 2, 'episode' => 1, 'parts' => 1, 'title' => 'The Return of Harmony', 'airs' => $ago('-2 days'), 'no' => 2, 'posted_by' => 9002, 'notes' => null, 'score' => 4.5],
      ['id' => 4, 'type' => 'episode', 'season' => 2, 'episode' => 2, 'parts' => 1, 'title' => 'Lesson Zero', 'airs' => $ago('+3 days'), 'no' => 3, 'posted_by' => 9002, 'notes' => null],
    ])->save();
    $this->table('show_votes')->insert([
      ['show_id' => 3, 'user_id' => 9001, 'vote' => 5],
      ['show_id' => 3, 'user_id' => 9002, 'vote' => 4],
    ])->save();
    $this->table('show_appearances')->insert([['show_id' => 3, 'appearance_id' => 5], ['show_id' => 3, 'appearance_id' => 6]])->save();

    // Reservations on the first episode in every state. 6: a request reserved two days ago; 7: reserved a month ago, so anyone who can reserve may
    // take it over; 8: a reservation nobody requested; 9: finished and waiting for approval; 10: approved
    $post = ['preview' => '/img/blank-pixel.png', 'fullsize' => '/img/blank-pixel.png', 'show_id' => 1, 'requested_at' => $ago('-10 days')];
    $this->table('posts')->insert([
      $post + ['id' => 6, 'type' => 'chr', 'label' => 'Recently Reserved Request', 'requested_by' => 9001, 'reserved_by' => 9008, 'reserved_at' => $ago('-2 days')],
      $post + ['id' => 7, 'type' => 'obj', 'label' => 'Overdue Reserved Request', 'requested_by' => 9001, 'reserved_by' => 9008, 'reserved_at' => $ago('-30 days')],
      $post + ['id' => 8, 'type' => 'chr', 'label' => 'Reservation Without A Request', 'requested_by' => null, 'requested_at' => null, 'reserved_by' => 9008, 'reserved_at' => $ago('-1 day')],
      $post + ['id' => 9, 'type' => 'chr', 'label' => 'Awaiting Approval Request', 'requested_by' => 9001, 'reserved_by' => 9008, 'reserved_at' => $ago('-5 days'), 'deviation_id' => 'dfin008', 'finished_at' => $ago('-1 day'), 'lock' => false],
      $post + ['id' => 10, 'type' => 'bg', 'label' => 'Approved Request', 'requested_by' => 9001, 'reserved_by' => 9008, 'reserved_at' => $ago('-6 days'), 'deviation_id' => 'dfin009', 'finished_at' => $ago('-2 days'), 'lock' => true],
    ])->save();
    $this->table('locked_posts')->insert([['post_id' => 10, 'user_id' => 9002, 'created_at' => $ago('-1 day'), 'updated_at' => $ago('-1 day')]])->save();

    // Site notices and useful links (the sidebar lists them), and a second signed-in session of the regular user
    $this->table('notices')->insert([
      ['id' => 1, 'type' => 'info', 'message_html' => 'Seeded notice: the club meets on Fridays.', 'hide_after' => $ago('+10 years'), 'posted_by' => 9002, 'created_at' => $now, 'updated_at' => $now],
    ])->save();
    $this->table('useful_links')->insert([
      ['id' => 1, 'url' => '/cg', 'label' => 'Color Guide', 'title' => 'Everything about the colors', 'minrole' => 'user', 'order' => 1],
      ['id' => 2, 'url' => '/admin', 'label' => 'Admin area', 'title' => 'For the staff', 'minrole' => 'staff', 'order' => 2],
    ])->save();
    $this->table('sessions')->insert([
      ['id' => 1, 'user_id' => 9001, 'platform' => 'Linux', 'browser_name' => 'Firefox', 'browser_ver' => '120.0', 'token' => hash('sha256', 'seeded-session-1'), 'created' => $ago('-3 days'), 'last_visit' => $ago('-1 day'),
        'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0', 'data' => null, 'updating' => false],
    ])->save();
  }
}
