<?php
namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MessageQueueBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_message_queue_job');
        $table->addColumn('job_progress', 'decimal', ['notnull' => false, 'precision' => 5, 'scale' => 2]);
    }
}
