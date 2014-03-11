<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSegmentBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_segment **/
        $table = $schema->createTable('oro_segment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('definition', 'text', []);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', []);
        $table->addColumn('last_run', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['type'], 'IDX_D02603B38CDE5729', []);
        $table->addIndex(['business_unit_owner_id'], 'IDX_D02603B359294170', []);
        /** End of generate table oro_segment **/

        /** Generate table oro_segment_type **/
        $table = $schema->createTable('oro_segment_type');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'UNIQ_29D10555EA750E8');
        /** End of generate table oro_segment_type **/

        /** Generate foreign keys for table oro_segment **/
        $table = $schema->getTable('oro_segment');
        $table->addForeignKeyConstraint($schema->getTable('oro_business_unit'), ['business_unit_owner_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_segment_type'), ['type'], ['name'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_segment **/

        // @codingStandardsIgnoreEnd
    }
}
