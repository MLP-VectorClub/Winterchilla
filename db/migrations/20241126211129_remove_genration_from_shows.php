<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

class RemoveGenrationFromShows extends AbstractMigration {
  public function up() {
    $this->table('show')->removeColumn('generation')->update();

    $this->query(/** @lang PostgreSQL */ "DROP TYPE mlp_generation");
  }

  public function down() {
    $this->query(sprintf(/** @lang PostgreSQL */ "CREATE TYPE mlp_generation AS ENUM ('%s', '%s')", ShowHelper::GEN_FIM, ShowHelper::GEN_PL));

    $this->table('show')
      ->addColumn('generation', Literal::from('mlp_generation'), ['null' => true])
      ->update();

    $this->query(sprintf("UPDATE show SET generation = '%s' WHERE type = 'episode' AND airs IS NOT NULL AND airs < date('2020-01-01')", ShowHelper::GEN_FIM));
  }
}
