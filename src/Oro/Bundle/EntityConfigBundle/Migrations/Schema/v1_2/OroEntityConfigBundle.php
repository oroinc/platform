<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;

class OroEntityConfigBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

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
        $table = $schema->getTable('oro_entity_config_value');

        /**
         * Drop serializable column from oro_entity_config_value
         */
        $table->dropColumn('serializable');

        /**
         * Change type and add Index for oro_entity_config_value::value
         */
        $table->getColumn('value')->setType(Type::getType(Type::STRING));
        $table->addIndex(['value']);

        /**
         * Rename table oro_entity_config_value
         */
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_entity_config_value',
            'oro_entity_config_index_value'
        );

        $entityTable = $schema->getTable('oro_entity_config');
        $entityTable->addColumn(
            'value',
            Type::getType(Type::TEXT)
        );

        $fieldTable = $schema->getTable('oro_entity_config_field');
        $fieldTable->addColumn(
            'value',
            Type::getType(Type::TEXT)
        );
    }
}
