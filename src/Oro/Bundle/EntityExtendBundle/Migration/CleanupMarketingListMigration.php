<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migration for cleaning up MarketingList bundle database tables and configurations.
 *
 * This migration is executed when the MarketingList bundle is not enabled in the application.
 * It removes all MarketingList-related database tables and associated entity configurations
 * to maintain a clean database schema.
 */
class CleanupMarketingListMigration implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new CleanupMarketingListMigrationQuery());
    }
}
