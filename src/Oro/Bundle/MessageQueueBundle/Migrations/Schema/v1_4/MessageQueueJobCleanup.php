<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\MessageQueue\Job\Job;

/**
 * Removes jobs from oro_message_job_queue:
 * - successes older than 2 weeks
 * - failed older than 1 month
 */
class MessageQueueJobCleanup implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $dateIntervalForStatusSuccess = (new \DateTime("-2 weeks", new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $dateIntervalForStatusFailed = (new \DateTime("-1 month", new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $queries->addPostQuery("
          DELETE 
          FROM  oro_message_queue_job 
          WHERE (status = '".Job::STATUS_FAILED."' AND stopped_at < '".$dateIntervalForStatusFailed."') 
          OR (status = '".Job::STATUS_SUCCESS."' AND stopped_at < '".$dateIntervalForStatusSuccess."')
        ");
    }
}
