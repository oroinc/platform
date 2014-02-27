<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class UpdateExtendConfigMigration implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        if ($schema instanceof ExtendSchema) {
            $options = $schema->getExtendOptions();
            //print_r($options);
        }

        return [];
    }
}
