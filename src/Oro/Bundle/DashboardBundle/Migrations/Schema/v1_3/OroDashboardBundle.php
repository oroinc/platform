<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDashboardBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::copyActiveDashboard($queries);
    }

    /**
     * Copy data from oro_dashboard_active into oro_dashboard_active_copy, rename and drop it
     *
     * @param QueryBag   $queries
     */
    public static function copyActiveDashboard(QueryBag $queries)
    {
        $queries->addPreQuery(
            "INSERT INTO oro_dashboard_active_copy (user_id, dashboard_id)
             SELECT user_id, dashboard_id
             FROM oro_dashboard_active;

             DROP TABLE oro_dashboard_active;
             ALTER TABLE oro_dashboard_active_copy RENAME TO oro_dashboard_active;"
        );
    }
}
