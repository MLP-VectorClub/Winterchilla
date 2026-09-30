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
      'preview'      => 'https://example.com/preview.png',
      'fullsize'     => 'https://example.com/full.png',
      'label'        => 'Seeded Test Request',
      'requested_at' => date('c'),
      'show_id'      => 1,
      'requested_by' => 9001,
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
  }
}
