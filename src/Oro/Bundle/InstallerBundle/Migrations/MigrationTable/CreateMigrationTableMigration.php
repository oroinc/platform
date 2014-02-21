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
        /*$table = $schema->createTable();
        $table->addColumn();
        $table->addIndex();
        $table->*/
        return [
            "CREATE TABLE " . self::MIGRATION_TABLE . " (
                id INT NOT NULL AUTO_INCREMENT,
                bundle VARCHAR(250) NOT NULL DEFAULT '',
                version VARCHAR(250) NOT NULL,
                date DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX " . self::MIGRATION_TABLE . "_bundle (bundle)
            );"
        ];
    }
}
