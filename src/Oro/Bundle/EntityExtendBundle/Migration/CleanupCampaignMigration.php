<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migration for cleaning up Campaign bundle database tables and configurations.
 *
 * This migration is executed when the Campaign bundle is not enabled in the application.
 * It removes all Campaign-related database tables and associated entity configurations
 * to maintain a clean database schema.
 */
class CleanupCampaignMigration implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new CleanupCampaignMigrationQuery());
    }
}
