<?php

namespace Oro\Bundle\MigrationBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;

/**
 * Handles pre-migration events to initialize migration state and create the migration tracking table.
 *
 * This listener is responsible for loading the latest migration versions for all bundles
 * from the database before migrations are executed. If the migration tracking table does not exist,
 * it registers a migration to create it. This ensures that the migration system can properly
 * track which migrations have been applied to each bundle.
 */
class PreUpMigrationListener
{
    public function onPreUp(PreMigrationEvent $event)
    {
        if ($event->isTableExist(CreateMigrationTableMigration::MIGRATION_TABLE)) {
            $data = $event->getData(
                sprintf(
                    'select * from %s where id in (select max(id) from %s group by bundle)',
                    CreateMigrationTableMigration::MIGRATION_TABLE,
                    CreateMigrationTableMigration::MIGRATION_TABLE
                )
            );
            foreach ($data as $val) {
                $event->setLoadedVersion($val['bundle'], $val['version']);
            }
        } else {
            $event->addMigration(new CreateMigrationTableMigration());
        }
    }
}
