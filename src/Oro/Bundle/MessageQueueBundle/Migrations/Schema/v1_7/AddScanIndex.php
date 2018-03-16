<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddScanIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_message_queue_job');
        $table->addIndex(['root_job_id', 'name', 'status', 'interrupted'], 'oro_message_queue_job_idx');
    }
}
