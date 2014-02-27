<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;

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
        return [];
    }
}
