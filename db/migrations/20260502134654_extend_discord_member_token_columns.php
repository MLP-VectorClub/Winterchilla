<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExtendDiscordMemberTokenColumns extends AbstractMigration
{
    public function up(): void
    {
      $this->table('discord_members')
        ->changeColumn('access', 'string', ['length' => 255])
        ->changeColumn('refresh', 'string', ['length' => 255])
        ->update();
    }

    public function down(): void
    {
      $this->table('discord_members')
        ->changeColumn('access', 'string', ['length' => 30])
        ->changeColumn('refresh', 'string', ['length' => 30])
        ->update();
    }
}
