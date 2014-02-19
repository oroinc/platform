<?php
namespace Oro\Bundle\InstallerBundle\Migrations\MigrationTable;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;
use Oro\Bundle\InstallerBundle\Migrations\MigrationsLoader;

class CreateTableMigration implements Migration
{
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
            "CREATE TABLE " . MigrationsLoader::MIGRATION_TABLE . " (
                id INT NOT NULL AUTO_INCREMENT,
                bundle VARCHAR(250) NOT NULL DEFAULT '',
                version VARCHAR(250) NOT NULL,
                date DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX " . MigrationsLoader::MIGRATION_TABLE . "_bundle (bundle)
            );"
        ];
    }
}
