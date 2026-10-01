<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * `20210123190129_adjust_show_unique_key` made the unique key on `show` `(season, episode, generation)`. Dropping the
 * `generation` column in `20241126211129_remove_genration_from_shows` made Postgres drop that index along with it, so
 * nothing has stopped duplicate episodes since. Movies and specials have no season/episode (NULLs never collide).
 */
final class RestoreShowSeasonEpisodeUniqueKey extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE UNIQUE INDEX IF NOT EXISTS show_season_episode_key ON show (season, episode)');
    }

    public function down(): void
    {
        $this->execute('DROP INDEX IF EXISTS show_season_episode_key');
    }
}
