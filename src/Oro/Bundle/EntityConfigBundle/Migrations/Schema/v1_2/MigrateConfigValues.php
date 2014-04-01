<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;

class MigrateConfigValues implements Migration, OrderedMigrationInterface, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
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
