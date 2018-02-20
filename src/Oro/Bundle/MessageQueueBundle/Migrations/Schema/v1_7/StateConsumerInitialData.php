<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Update initial data for customers heartbeat state.
 * We need to add some value to the consumers record to simplify heartbeat state update query.
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
                'UPDATE oro_message_queue_state SET updated_at = :updated_at WHERE id=:id and updated_at is null',
                ['id' => 'consumers', 'updated_at' =>  new \DateTime('2000-01-01')],
                ['id' => Type::STRING, 'updated_at' => Type::DATETIME]
            )
        );
    }
}
