<?php

namespace Oro\Bundle\InstallerBundle\Migrations\MigrationTable;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class CreateMigrationTableMigration implements Migration
{
    const MIGRATION_TABLE = 'oro_installer_migrations';

    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable(self::MIGRATION_TABLE);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('bundle', 'string', ['length' => 250]);
        $table->addColumn('version', 'string', ['length' => 250]);
        $table->addColumn('date', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['bundle'], sprintf('IDX_%s__bundle', self::MIGRATION_TABLE));

        return [];
    }
}
