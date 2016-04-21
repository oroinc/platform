<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroReportBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_6';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroReportTypeTable($schema);
        $this->createOroReportTable($schema);

        /** Foreign keys generation **/
        $this->addOroReportForeignKeys($schema);
    }

    /**
     * Create oro_report_type table
     *
     * @param Schema $schema
     */
    protected function createOroReportTypeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_report_type');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addUniqueIndex(['label'], 'uniq_397d3359ea750e8');
        $table->setPrimaryKey(['name']);
    }

    /**
     * Create oro_report table
     *
     * @param Schema $schema
     */
    protected function createOroReportTable(Schema $schema)
    {
        $table = $schema->createTable('oro_report');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('definition', 'text', []);
        $table->addColumn('createdat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updatedat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('chart_options', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->addIndex(['business_unit_owner_id'], 'idx_b48821b659294170', []);
        $table->addIndex(['organization_id'], 'idx_b48821b632c8a3de', []);
        $table->addIndex(['type'], 'idx_b48821b68cde5729', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_report foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroReportForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_report');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_report_type'),
            ['type'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
