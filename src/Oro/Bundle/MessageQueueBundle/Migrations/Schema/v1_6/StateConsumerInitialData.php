<?php

namespace Oro\Bundle\MessageQueueBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds initial data for customers heartbeat state.
 */
class StateConsumerInitialData implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'INSERT INTO oro_message_queue_state (id, updated_at) VALUES (:id, :updated_at)',
                ['id' => 'consumers', 'updated_at' => null],
                ['id' => Types::STRING, 'updated_at' => Types::DATETIME_MUTABLE]
            )
        );
    }
}
