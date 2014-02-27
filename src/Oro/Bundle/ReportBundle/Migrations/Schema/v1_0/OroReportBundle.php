<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class OroReportBundle implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_report **/
        $table = $schema->createTable('oro_report');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('definition', 'text', []);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['type'], 'IDX_B48821B68CDE5729', []);
        $table->addIndex(['business_unit_owner_id'], 'IDX_B48821B659294170', []);
        /** End of generate table oro_report **/

        /** Generate table oro_report_type **/
        $table = $schema->createTable('oro_report_type');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'UNIQ_397D3359EA750E8');
        /** End of generate table oro_report_type **/

        /** Generate foreign keys for table oro_report **/
        $table = $schema->getTable('oro_report');
        $table->addForeignKeyConstraint($schema->getTable('oro_business_unit'), ['business_unit_owner_id'], ['id'], ['onDelete' => 'SET NULL', 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('oro_report_type'), ['type'], ['name'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table oro_report **/

        // @codingStandardsIgnoreEnd

        return [];
    }
}
