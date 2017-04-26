<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updates job_progress column value from old format (from 0 to 100) to the new (from 0 to 1)
 */
class MessageQueueBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            'UPDATE oro_message_queue_job SET job_progress = job_progress/100 WHERE job_progress > 1'
        );
    }
}
