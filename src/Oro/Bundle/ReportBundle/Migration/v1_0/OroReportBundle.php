<?php

namespace Oro\Bundle\ReportBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

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
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('business_unit_owner_id', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('type', 'string', ['default' => null, 'notnull' => false, 'length' => 32, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('description', 'text', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('entity', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('definition', 'text', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('createdAt', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('updatedAt', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['type'], 'IDX_B48821B68CDE5729', []);
        $table->addIndex(['business_unit_owner_id'], 'IDX_B48821B659294170', []);
        /** End of generate table oro_report **/

        /** Generate table oro_report_type **/
        $table = $schema->createTable('oro_report_type');
        $table->addColumn('name', 'string', ['default' => null, 'notnull' => true, 'length' => 32, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('label', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
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
