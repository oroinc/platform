<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Schemas\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroInstallerBundle implements Migration
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
