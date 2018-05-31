<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes "is_head" column from "oro_activity_list" table.
 */
class RemoveHeadColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_activity_list');
        $table->dropIndex('al_is_head');
        $table->dropColumn('is_head');
        $queries->addPostQuery(
            new RemoveFieldQuery('Oro\Bundle\ActivityListBundle\Entity\ActivityList', 'head')
        );
    }
}
