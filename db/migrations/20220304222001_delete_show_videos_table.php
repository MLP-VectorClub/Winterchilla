<?php

use Phinx\Migration\AbstractMigration;

class DeleteShowVideosTable extends AbstractMigration
{
    public function up()
    {
        $this->table('show_videos')->drop()->save();

        $this->query("DELETE FROM logs WHERE entry_type = 'video_broken'");
    }

    public function down()
    {
        $this->table('show_videos')
            ->addIndex('id')
            ->addColumn('provider_abbr', 'char', ['length' => 2])
            ->addColumn('provider_id', 'string', ['length' => 64])
            ->addColumn('part', 'integer', ['default' => 1])
            ->addColumn('fullep', 'boolean', ['default' => true])
            ->addColumn('created_at', 'timestamp', ['timezone' => true, 'null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['timezone' => true, 'null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('not_broken_at', 'timestamp', ['timezone' => true, 'null' => true])
            ->addColumn('show_id', 'integer', ['null' => false])
            ->addForeignKey('show_id', 'show', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
