<?php

namespace Oro\Bundle\MessageQueueBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes extra indexes from `oro_message_queue_job` table,
 * note that `oro_message_queue_job` is heavy write table.
 */
class MessageQueueBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_message_queue_job');

        if ($table->hasIndex('owner_id_idx')) {
            $table->dropIndex('owner_id_idx');
        }

        if ($table->hasIndex('oro_message_queue_job_idx')) {
            $table->dropIndex('oro_message_queue_job_idx');
        }

        if (!$table->hasIndex('idx_status')) {
            $table->addIndex(['status'], 'idx_status');
        }
    }
}
