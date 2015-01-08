<?php

namespace Oro\Bundle\BatchBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class OroBatchBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->deleteJobExecutions($queries);
        $this->deleteObsoleteJobInstances($queries);
    }

    /**
     * Delete old akeneo job execution records
     *
     * @param QueryBag $queries
     */
    protected function deleteJobExecutions(QueryBag $queries)
    {
        $batchStatuses = [BatchStatus::STARTING, BatchStatus::STARTED];
        $date          = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->sub(\DateInterval::createFromDateString('1 month'));
        $endTime = $date->format('Y-m-d H:i:s');

        $sql = <<<SQL
    DELETE
    FROM
      akeneo_batch_job_execution
    WHERE status NOT IN (%s)
      AND create_time < '%s'
SQL;
        $queries->addPostQuery(new SqlMigrationQuery(sprintf($sql, implode(',', $batchStatuses), $endTime)));
    }

    /**
     * Delete old akeneo job instance records
     *
     * @param QueryBag $queries
     */
    protected function deleteObsoleteJobInstances(QueryBag $queries)
    {
        $sql = <<<SQL
    DELETE
    FROM
      akeneo_batch_job_instance
    WHERE NOT EXISTS
      (SELECT
        je.id
      FROM
        akeneo_batch_job_execution je
      WHERE je.job_instance_id = akeneo_batch_job_instance.id)
SQL;
        $queries->addPostQuery(new SqlMigrationQuery($sql));
    }
}
