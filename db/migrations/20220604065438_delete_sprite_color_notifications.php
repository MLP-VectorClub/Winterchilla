<?php

use Phinx\Migration\AbstractMigration;

class DeleteSpriteColorNotifications extends AbstractMigration
{
  public function up()
  {
    $this->query("DELETE FROM notifications WHERE type = 'sprite-colors'");

    $this->table('notifications')->removeColumn('read_action')->update();
  }

  public function down()
  {
    $this->table('notifications')
      ->addColumn('read_action', 'string', ['length' => 15, 'null' => true])
      ->update();
  }
}
