<?php

use Phinx\Migration\AbstractMigration;

class RemoveSynopsisFeature extends AbstractMigration
{
  public function up()
  {
    $this->query("DELETE FROM user_prefs WHERE key = 'ep_hidesynopses'");

    $this->table('show')->removeColumn('synopsis_last_checked')->update();
  }

  public function down()
  {
    $this->table('show')
      ->addColumn('synopsis_last_checked', 'timestamp', ['timezone' => true, 'null' => true])
      ->update();
  }
}
