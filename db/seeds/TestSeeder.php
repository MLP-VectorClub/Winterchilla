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
    ])->save();

    // DeviantArt linked accounts (access_expires far in the future to avoid token refresh)
    $this->table('deviantart_users')->insert([
      [
        // Fixed IDs (TestSeederConstants::*_DA_ID) so the fake OAuth provider can sign in as these users
        'id'             => '0f0e0d0c-0b0a-4000-8000-000000009001',
        'name'           => 'TestUser',
        'avatar_url'     => null,
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
        'avatar_url'     => null,
        'user_id'        => 9002,
        'scope'          => 'user',
        'access'         => 'fake-access-token-admin',
        'refresh'        => 'fake-refresh-token-admin',
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
      'preview'      => 'http://127.0.0.1:8765/img/blank-pixel.png',
      'fullsize'     => 'http://127.0.0.1:8765/img/blank-pixel.png',
      'label'        => 'Seeded Test Request',
      'requested_at' => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
    ], [
      // Requests for the API tests: 2 is deleted by its owner, 3 is already reserved (so its owner can't delete it)
      'id'           => 2,
      'type'         => 'chr',
      'preview'      => 'http://127.0.0.1:8765/img/blank-pixel.png',
      'fullsize'     => 'http://127.0.0.1:8765/img/blank-pixel.png',
      'label'        => 'Deletable Test Request',
      'requested_at' => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
    ], [
      'id'           => 3,
      'type'         => 'obj',
      'preview'      => 'http://127.0.0.1:8765/img/blank-pixel.png',
      'fullsize'     => 'http://127.0.0.1:8765/img/blank-pixel.png',
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
      'preview'      => 'http://127.0.0.1:8765/img/blank-pixel.png',
      'fullsize'     => 'http://127.0.0.1:8765/img/blank-pixel.png',
      'label'        => 'Broken Test Request',
      'requested_at' => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
      'broken'       => true,
    ], [
      // Like 4, but cleared through the UI test
      'id'           => 5,
      'type'         => 'obj',
      'preview'      => 'http://127.0.0.1:8765/img/blank-pixel.png',
      'fullsize'     => 'http://127.0.0.1:8765/img/blank-pixel.png',
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
    foreach (['d1b2c3d' => 'Seeded Entry', 'd1b2c3e' => 'Admin Entry', 'd1b2c3f' => 'Doomed Entry', 'd1b2c3g' => 'Withdrawn Entry', 'dfin001' => 'Finished Test Vector', 'dfin002' => 'Vector For Finishing', 'dfin003' => 'Vector For The UI Test', 'dfin004' => 'Vector By Someone Else', 'dfin005' => 'Image For New Posts', 'dfin006' => 'Image For Changing', 'dfin007' => 'Image For The UI Test'] as $sub_id => $title)
      \App\Models\CachedDeviation::create([
        'provider' => 'fav.me',
        'id' => $sub_id,
        'title' => $title,
        'author' => in_array($sub_id, ['dfin002', 'dfin003'], true) ? 'TestAdmin' : 'TestUser',
        // Made-up deviations point at an image the test server serves, so nothing has to leave the machine; the
        // query string keeps the URLs unique (posts refuse an image that another post already uses)
        'preview' => "http://127.0.0.1:8765/img/blank-pixel.png?d=$sub_id",
        'fullsize' => "http://127.0.0.1:8765/img/blank-pixel.png?d=$sub_id",
        'type' => 'png',
      ]);

    // Rows with explicit IDs don't advance their sequences, so the next row created by the app would collide
    foreach (['appearances', 'color_groups', 'cutiemarks', 'show', 'posts', 'notifications', 'events', 'event_entries'] as $table)
      $this->execute("SELECT setval(pg_get_serial_sequence('$table', 'id'), GREATEST((SELECT MAX(id) FROM $table), 1))");
  }
}
