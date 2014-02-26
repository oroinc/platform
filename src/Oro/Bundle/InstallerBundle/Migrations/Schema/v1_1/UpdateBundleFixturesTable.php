<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class UpdateBundleFixturesTable implements Migration
{
    /**
     * Add oro_installer_data_fixtures table
     *
     * @param Schema $schema
     */
    public static function oroInstallerDataFixturesTable(Schema $schema)
    {
        $table = $schema->createTable('oro_installer_data_fixtures');
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('class_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255]);
        $table->addColumn('loaded_at', 'datetime', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        $schema->dropTable('oro_installer_bundle_version');

        self::oroInstallerDataFixturesTable($schema);

        return [];
    }
}
