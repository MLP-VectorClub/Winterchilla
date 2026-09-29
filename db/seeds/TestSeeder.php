<?php

use Phinx\Seed\AbstractSeed;
use Ramsey\Uuid\Uuid;

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
        'id'             => Uuid::uuid4(),
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
        'id'             => Uuid::uuid4(),
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

    // A show entry (S01E01)
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
    ]])->save();

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
