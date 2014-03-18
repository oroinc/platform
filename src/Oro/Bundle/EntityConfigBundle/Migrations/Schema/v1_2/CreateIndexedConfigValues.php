<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;

class CreateIndexedConfigValues implements Migration, OrderedMigrationInterface, RenameExtensionAwareInterface
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
        return 2;
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
        $queries->addPreQuery($this->getRemoveObsoleteValuesSql());

        $table = $schema->getTable('oro_entity_config_value');
        $table->dropColumn('serializable');
        $table->getColumn('value')
            ->setType(Type::getType(Type::STRING));

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_entity_config_value',
            'oro_entity_config_index_value'
        );
    }

    /**
     * @return string
     */
    protected function getRemoveObsoleteValuesSql()
    {
        return 'DELETE FROM oro_entity_config_value WHERE NOT ('
        . " (scope = 'dataaudit' AND code = 'auditable')" // for both entity and field
        . " OR (scope = 'entity' AND code = 'label')" // for both entity and field
        . " OR (scope = 'entity' AND code = 'label')" // for both entity and field
        . " OR (scope = 'extend' AND code IN ('owner', 'state', 'is_deleted'))" // for both entity and field
        . " OR (scope = 'extend' AND code = 'is_extend' AND field_id IS NULL)" // for entity only
        . " OR (scope = 'ownership' AND code = 'owner_type' AND field_id IS NULL)" // for entity only
        . ')';
    }
}
