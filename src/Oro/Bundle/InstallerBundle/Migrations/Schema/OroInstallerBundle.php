<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Installation;
use Oro\Bundle\InstallerBundle\Migrations\Schema\v1_1\UpdateBundleFixturesTable;

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
        UpdateBundleFixturesTable::oroInstallerDataFixturesTable($schema);

        return [];
    }
}
