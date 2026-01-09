<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migration for cleaning up Tracking bundle database tables and configurations.
 *
 * This migration is executed when the Tracking bundle is not enabled in the application.
 * It removes all Tracking-related database tables and associated entity configurations
 * to maintain a clean database schema.
 */
class CleanupTrackingMigration implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new CleanupTrackingMigrationQuery());
    }
}
