<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroReportBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v2_4';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroReportTypeTable($schema);
        $this->createOroReportTable($schema);
        $this->createOroCalendarDateTable($schema);

        /** Foreign keys generation **/
        $this->addOroReportForeignKeys($schema);
    }

    /**
     * Create oro_report_type table
     */
    private function createOroReportTypeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_report_type');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['name']);
        $table->addUniqueIndex(['label'], 'uniq_397d3359ea750e8');
    }

    /**
     * Create oro_report table
     */
    private function createOroReportTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_report');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('definition', 'text');
        $table->addColumn('createdat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updatedat', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('chart_options', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['business_unit_owner_id'], 'idx_b48821b659294170');
        $table->addIndex(['organization_id'], 'idx_b48821b632c8a3de');
        $table->addIndex(['type'], 'idx_b48821b68cde5729');
    }

    /**
     * Create oro_calendar_date table
     */
    private function createOroCalendarDateTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_calendar_date');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('date', 'date', ['comment' => '(DC2Type:date)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['date'], 'oro_calendar_date_date_unique_idx');
    }

    /**
     * Add oro_report foreign keys.
     */
    private function addOroReportForeignKeys(Schema $schema): void
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
