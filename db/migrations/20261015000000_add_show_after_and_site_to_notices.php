<?php

use Phinx\Migration\AbstractMigration;

/**
 * A notice can start showing later than it was written (`show_after`, NULL = at once) and can be meant for one of the two sites that share the
 * database during the cutover (`site`: all, celestia or winterchilla). Luna's migration adds the same columns, so each one checks first.
 */
class AddShowAfterAndSiteToNotices extends AbstractMigration {
  public function up() {
    $table = $this->table('notices');
    if (!$table->hasColumn('show_after'))
      $table->addColumn('show_after', 'timestamp', ['timezone' => true, 'null' => true]);
    if (!$table->hasColumn('site'))
      $table->addColumn('site', 'string', ['limit' => 16, 'default' => 'all', 'null' => false]);
    $table->update();
  }

  public function down() {
    $this->table('notices')->removeColumn('show_after')->removeColumn('site')->update();
  }
}
