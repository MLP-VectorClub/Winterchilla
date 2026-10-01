<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * `cg_defaultguide` is the id of a guide (`pony` or `eqg`). Two production rows hold `pl`, which was never a guide, and
 * an implementation that validates the preference as an enum cannot read them. Deleting the row resets it to the default.
 */
final class ClearInvalidDefaultGuidePrefs extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("DELETE FROM user_prefs WHERE key = 'cg_defaultguide' AND value NOT IN ('pony', 'eqg')");
    }

    public function down(): void
    {
        // The invalid values are not recoverable, and nothing could use them
    }
}
