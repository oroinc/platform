<?php

declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\EventListener;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;

/**
 * Resets a specific migration version for a bundle before migrations are executed.
 *
 * When a record matching the configured bundle and version exists in the migration tracking table,
 * it is renamed to the new version.
 */
class ResetMigrationVersionListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $bundleName,
        private readonly string $version,
        private readonly string $newVersion,
    ) {
    }

    public function onPreUp(PreMigrationEvent $event): void
    {
        $migrationTable = CreateMigrationTableMigration::MIGRATION_TABLE;

        if (!$event->isTableExist($migrationTable)) {
            return;
        }

        $this->connection->update(
            $migrationTable,
            ['version' => $this->newVersion],
            [
                'bundle' => $this->bundleName,
                'version' => $this->version,
            ]
        );
    }
}
