<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddNavigationHistoryItemIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_navigation_history');

        if (!$table->hasIndex('oro_navigation_history_user_org_idx')) {
            $table->addIndex(['user_id', 'organization_id'], 'oro_navigation_history_user_org_idx', []);
        }
    }
}
