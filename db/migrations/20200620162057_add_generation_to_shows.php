<?php /** @noinspection SqlResolve */

use App\ShowHelper;
use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

class AddGenerationToShows extends AbstractMigration {
  public function up() {
    $this->query(/** @lang PostgreSQL */ "CREATE TYPE mlp_generation AS ENUM ('pony', 'pl')");

    $this->table('show')
      ->addColumn('generation', Literal::from('mlp_generation'), ['null' => true])
      ->update();

    $this->query(sprintf("UPDATE show SET generation = '%s' WHERE type = 'episode' AND airs IS NOT NULL AND airs < date('2020-01-01')", 'pony'));
  }

  public function down() {
    $this->table('show')->removeColumn('generation')->update();

    $this->query(/** @lang PostgreSQL */ "DROP TYPE mlp_generation");
  }
}
