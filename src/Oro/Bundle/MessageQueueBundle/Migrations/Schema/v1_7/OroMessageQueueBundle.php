<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroMessageQueueBundle implements Migration, ConnectionAwareInterface
{
    /** @var Connection */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return;
        }

        $table = $schema->getTable('oro_message_queue');
        if (!$table->hasIndex('idx_oro_message_queue_pri_id')) {
            $queries->addPostQuery(
                'CREATE INDEX idx_oro_message_queue_pri_id on oro_message_queue (priority DESC, id ASC)'
            );
        }
    }

    /**
     * Sets the database connection
     *
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
