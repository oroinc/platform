<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroActivityListBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addColumns($schema);
    }

    /**
     * Add group head field
     *
     * @param Schema $schema
     */
    public static function addColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_activity_list');
        $table->addColumn('is_head', 'boolean', ['default' => true]);

        $table->addIndex(['related_activity_class'], 'al_related_activity_class');
        $table->addIndex(['related_activity_id'], 'al_related_activity_id');
        $table->addIndex(['is_head'], 'al_is_head');
    }
}
