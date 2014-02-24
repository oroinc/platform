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
        $table->addColumn('id', 'integer', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('bundle', 'string', ['default' => '', 'notnull' => true, 'length' => 250, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('version', 'string', ['default' => null, 'notnull' => true, 'length' => 250, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('date', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['id', ]);
        $table->addIndex(['bundle', ], 'oro_installer_migrations_bundle', []);
        /** End of generate table oro_installer_migrations **/
        // @codingStandardsIgnoreEnd

        return [];
    }
}
