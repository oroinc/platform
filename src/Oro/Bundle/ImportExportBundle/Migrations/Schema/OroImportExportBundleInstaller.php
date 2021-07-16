<?php

namespace Oro\Bundle\ImportExportBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImportExportBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createImportExportResultTable($schema);
        $this->addForeignKeys($schema);
    }

    private function createImportExportResultTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_import_export_result');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('filename', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('job_id', 'integer', ['unique' => true, 'notnull' => true]);
        $table->addColumn('type', 'string', ['unique' => false, 'length' => 255, 'notnull' => true]);
        $table->addColumn('entity', 'string', ['unique' => false, 'length' => 255, 'notnull' => true]);
        $table->addColumn('options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('expired', 'boolean', ['default' => '0']);
        $table->addColumn('created_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['filename'], 'UNIQ_F24BC1D93C0BE965');
        $table->addUniqueIndex(['job_id'], 'UNIQ_F24BC1D95DBAE92F');
        $table->addIndex(['owner_id'], 'IDX_F24BC1D97E3C61F9', []);
        $table->addIndex(['organization_id'], 'IDX_F24BC1D932C8A3DE', []);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function addForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_import_export_result');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }
}
