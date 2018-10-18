<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add search weight field to standard search ORM implementation
 */
class AddSearchWeightField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_search_item');

        if (!$table->hasColumn('weight')) {
            $table->addColumn('weight', 'decimal', ['precision' => 21, 'scale' => 8, 'default' => 1]);
        }
    }
}
