<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSegmentBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addColumns($schema);
    }

    /**
     * Add recordsLimit to segment
     *
     * @param Schema $schema
     */
    public static function addColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_segment');
        $table->addColumn('records_limit', 'integer', ['notnull' => false]);
    }
}
