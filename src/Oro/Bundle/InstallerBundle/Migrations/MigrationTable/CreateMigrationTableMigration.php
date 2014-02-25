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
        // @codingStandardsIgnoreStart
        /** Generate table oro_installer_migrations **/
        $table = $schema->createTable(self::MIGRATION_TABLE);
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('bundle', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('version', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('date', 'datetime', ['notnull' => true]);
        $table->setPrimaryKey(['id', ]);
        $table->addIndex(['bundle', ], sprintf('IDX_%s__bundle', self::MIGRATION_TABLE), []);
        /** End of generate table oro_installer_migrations **/
        // @codingStandardsIgnoreEnd

        return [];
    }
}
