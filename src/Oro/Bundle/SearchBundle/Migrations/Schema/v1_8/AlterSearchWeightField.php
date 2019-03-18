<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Change precision and scale options for weight field
 */
class AlterSearchWeightField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery('UPDATE oro_search_item SET weight = 9999.9999 WHERE weight >= 10000');

        $table = $schema->getTable('oro_search_item');
        $table->getColumn('weight')->setOptions(['precision' => 8, 'scale' => 4]);
    }
}
