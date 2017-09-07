<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class StateTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createStateTable($schema);
        $this->initializeCacheState($queries);
    }

    /**
     * Adds the oro_message_queue_state table structure.
     *
     * @param Schema $schema
     */
    private function createStateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_message_queue_state');
        $table->addColumn('id', 'string', ['length' => 15, 'notnull' => true]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Adds the initial cache state data to oro_message_queue_state table.
     *
     * @param QueryBag $queries
     */
    private function initializeCacheState(QueryBag $queries)
    {
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'INSERT INTO oro_message_queue_state (id, updated_at) VALUES (:id, :updated_at)',
                ['id' => 'cache', 'updated_at' => null],
                ['id' => Type::STRING, 'updated_at' => Type::DATETIME]
            )
        );
    }
}
