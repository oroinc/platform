<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v1_4;

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
        $table->addColumn('workerName', 'string', ['length' => 50, 'notnull' => false]);
        /** End of update table jms_jobs **/

        /** Generate table jms_cron_job **/
        $table = $schema->createTable('jms_cron_jobs');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('command', 'string', ['length' => 200 ]);
        $table->addColumn('lastRunAt', 'datetime', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['command'], 'UNIQ_55F5ED428ECAEAD4', []);
        /** End of generate table jms_cron_job **/
    }
}
