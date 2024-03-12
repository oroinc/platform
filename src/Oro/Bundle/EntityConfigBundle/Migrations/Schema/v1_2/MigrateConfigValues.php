<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateConfigValues implements Migration, OrderedMigrationInterface, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->getTable('oro_entity_config')
            ->addColumn('data', 'array', ['notnull' => false]);
        $schema->getTable('oro_entity_config_field')
            ->addColumn('data', 'array', ['notnull' => false]);

        $queries->addQuery(new MigrateConfigValuesQuery());
    }
}
