<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Indexes for performance optimization
 */
class AddIndexes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addIndexes($schema, 'oro_search_index_decimal');
        $this->addIndexes($schema, 'oro_search_index_integer');
        $this->addIndexes($schema, 'oro_search_index_datetime');
        $this->addIndexes($schema, 'oro_search_index_text');
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     */
    protected function addIndexes(Schema $schema, $tableName)
    {
        $table = $schema->getTable($tableName);

        $fieldIndexName = $tableName . '_field_idx';
        if (!$table->hasIndex($fieldIndexName)) {
            $table->addIndex(['field'], $fieldIndexName);
        }

        $itemFieldIndexName = $tableName . '_item_field_idx';
        if (!$table->hasIndex($itemFieldIndexName)) {
            $table->addIndex(['item_id', 'field'], $itemFieldIndexName);
        }
    }
}
