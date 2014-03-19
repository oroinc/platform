<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_0\OroEntityConfigBundle;

class OroEntityConfigBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroEntityConfigBundle::oroEntityConfigTable($schema);
        OroEntityConfigBundle::oroEntityConfigFieldTable($schema);
        OroEntityConfigBundle::oroEntityConfigLogTable($schema);
        OroEntityConfigBundle::oroEntityConfigLogDiffTable($schema);
        OroEntityConfigBundle::oroEntityConfigOptionsetTable($schema);
        OroEntityConfigBundle::oroEntityConfigOptionsetRelationTable($schema, 'oro_entity_config_optset_rel');
        OroEntityConfigBundle::oroEntityConfigValueTable($schema);

        OroEntityConfigBundle::oroEntityConfigLogDiffForeignKeys($schema);
        OroEntityConfigBundle::oroEntityConfigOptionsetForeignKeys($schema);
        OroEntityConfigBundle::oroEntityConfigOptionsetRelationForeignKeys($schema, 'oro_entity_config_optset_rel');
        OroEntityConfigBundle::oroEntityConfigValueForeignKeys($schema);
        OroEntityConfigBundle::oroEntityConfigFieldForeignKeys($schema);
        OroEntityConfigBundle::oroEntityConfigLogForeignKeys($schema);
    }
}
