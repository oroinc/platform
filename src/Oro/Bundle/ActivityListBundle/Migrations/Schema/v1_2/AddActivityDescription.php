<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddActivityDescription implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addColumns($schema);
    }

    /**
     * Add description to activity list
     *
     * @param Schema $schema
     */
    public static function addColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_activity_list');
        $table->addColumn('description', 'text', ['notnull' => false]);
    }
}
