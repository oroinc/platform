<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Deletes unused tables and columns.
 */
class OroBatchBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('akeneo_batch_step_execution');
        if ($table->hasColumn('filter_count')) {
            $table->dropColumn('filter_count');
        }

        if ($schema->hasTable('akeneo_batch_mapping_field')) {
            $schema->dropTable('akeneo_batch_mapping_field');
        }

        if ($schema->hasTable('akeneo_batch_mapping_item')) {
            $schema->dropTable('akeneo_batch_mapping_item');
        }
    }
}
