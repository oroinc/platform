<?php

namespace Oro\Bundle\MigrationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroMigrationBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroMigrationsDataTable($schema);
    }

    /**
     * Create oro_migrations_data table
     *
     * @param Schema $schema
     */
    protected function createOroMigrationsDataTable(Schema $schema)
    {
        $table = $schema->createTable('oro_migrations_data');
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('class_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255]);
        $table->addColumn('loaded_at', 'datetime', ['notnull' => true]);
        $table->addColumn('version', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }
}
