<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroIntegrationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroIntegrationFieldsChangesTable($schema);
    }

    /**
     * Create oro_integration_change_set table
     *
     * @param Schema $schema
     */
    protected function createOroIntegrationFieldsChangesTable(Schema $schema)
    {
        $table = $schema->createTable('oro_integration_fields_changes');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer', []);
        $table->addColumn('changed_fields', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['entity_id', 'entity_class'], 'oro_integration_fields_changes_idx', []);
    }
}
