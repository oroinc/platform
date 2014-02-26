<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\DBAL\Schema\Schema as BaseSchema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class UpdateExtendConfigMigration implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(BaseSchema $schema)
    {
        if ($schema instanceof Schema) {
            $options = $schema->getExtendOptions();
            var_dump($options);
        }

        return [];
    }
}
