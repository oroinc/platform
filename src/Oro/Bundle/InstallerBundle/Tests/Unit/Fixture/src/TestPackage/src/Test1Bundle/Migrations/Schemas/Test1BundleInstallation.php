<?php

namespace Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Installation;

class Test1BundleInstallation implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return "v1_0";
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)",
        ];
    }
}
