<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Schemas;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Installation;

class OroInstallerBundle implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return "v1_1";
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        // add oro_installer_data_fixtures table
        $table = $schema->createTable('oro_installer_data_fixtures');
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('class_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255]);
        $table->addColumn('loaded_at', 'datetime', ['notnull' => true]);
        $table->setPrimaryKey(['id']);

        return [];
    }
}
