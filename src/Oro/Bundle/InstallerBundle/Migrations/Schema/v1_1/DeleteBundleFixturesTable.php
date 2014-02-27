<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class DeleteBundleFixturesTable implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        $schema->dropTable('oro_installer_bundle_version');

        return [];
    }
}
