<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroMessageQueueJobOwnerIdIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_message_queue_job');
        if (!$table->hasIndex("owner_id_idx")) {
            $table->addIndex(['owner_id'], "owner_id_idx");
        }
    }
}
