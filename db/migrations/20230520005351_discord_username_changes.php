<?php

use Phinx\Migration\AbstractMigration;

class DiscordUsernameChanges extends AbstractMigration
{
  public function up()
  {
    $this->query('ALTER TABLE discord_members ADD display_name character varying(32)');
    $this->query('ALTER TABLE discord_members ALTER COLUMN discriminator TYPE smallint USING discriminator::smallint');
  }

  public function down()
  {
    $this->query('ALTER TABLE discord_members DROP COLUMN display_name');
    $this->query(<<<SQL
        ALTER TABLE discord_members ALTER COLUMN "discriminator" TYPE character(4) USING substring(('0000' || "discriminator") from '\\d{4}$')
        SQL
    );
  }
}
