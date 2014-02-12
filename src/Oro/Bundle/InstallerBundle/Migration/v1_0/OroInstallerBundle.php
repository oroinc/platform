<?php

namespace Oro\Bundle\InstallerBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroInstallerBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_installer_bundle_version (id INT AUTO_INCREMENT NOT NULL, bundle_name VARCHAR(150) NOT NULL, data_version VARCHAR(15) DEFAULT NULL, demo_data_version VARCHAR(15) DEFAULT NULL, PRIMARY KEY(id));"
        ];
    }
}
