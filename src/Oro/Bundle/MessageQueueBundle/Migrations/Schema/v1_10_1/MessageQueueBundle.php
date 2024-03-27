<?php

namespace Oro\Bundle\MessageQueueBundle\Migrations\Schema\v1_10_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Improve performance of message queue jobs retrieval.
 */
class MessageQueueBundle implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_message_queue_job');

        if ($table->hasIndex('oro_message_queue_job_inx')) {
            return;
        }

        $table->addIndex(['root_job_id', 'name', 'owner_id'], 'oro_message_queue_job_inx');
    }
}
