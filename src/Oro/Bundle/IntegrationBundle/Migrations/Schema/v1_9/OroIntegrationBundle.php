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
        $this->createOroIntegrationChangeSetTable($schema);
    }

    /**
     * Create oro_integration_change_set table
     *
     * @param Schema $schema
     */
    protected function createOroIntegrationChangeSetTable(Schema $schema)
    {
        $table = $schema->createTable('oro_integration_change_set');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer', []);
        $table->addColumn('local_changes', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('remote_changes', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
    }
}
