<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class JmsJob implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Update table jms_jobs **/
        $table = $schema->getTable('jms_jobs');
        $table->dropIndex('IDX_704ADB938ECAEAD4');
        $table->dropIndex('job_runner');

        $table->addColumn('queue', 'string', ['length' => Job::MAX_QUEUE_LENGTH]);
        $table->addColumn('priority', 'smallint', ['notnull' => true]);

        $table->changeColumn('state', ['length' => 15]);

        $table->addIndex(['command'], 'cmd_search_index', []);
        $table->addIndex(['state', 'priority', 'id'], 'sorting_index', []);
        /** End of update table jms_jobs **/
    }
}
