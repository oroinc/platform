<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createOroIntegrationStateTable($schema);
    }

    /**
     * Creates table oro_integration_entity_state
     *
     * @param Schema $schema
     */
    public function createOroIntegrationStateTable(Schema $schema)
    {
        /** Generate table oro_integration_entity_state **/
        $table = $schema->createTable('oro_integration_entity_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer', []);
        $table->addColumn('state', 'smallint', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_class', 'entity_id'], 'oro_entity_class_id_idx', []);
        $table->addIndex(['entity_class', 'entity_id', 'state'], 'oro_entity_class_id_state_idx', []);
    }
}
