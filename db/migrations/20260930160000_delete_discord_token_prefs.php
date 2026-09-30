<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Discord account linking used to work by having users paste a short verification code (stored as the
 * `discord_token` preference) into Discord. It was replaced by OAuth long ago and nothing reads the
 * preference anymore, so the leftover codes are just dead credentials.
 */
final class DeleteDiscordTokenPrefs extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("DELETE FROM user_prefs WHERE key = 'discord_token'");
    }

    public function down(): void
    {
        // The deleted verification codes are not recoverable, and nothing uses them
    }
}
