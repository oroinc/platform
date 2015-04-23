<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_3;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateSegmentSnapshotDataQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->updateSnapshotData($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->updateSnapshotData($logger);
    }

    /**
     * Collect integer_entity_id field in oro_segment_snapshot table
     *
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function updateSnapshotData(LoggerInterface $logger, $dryRun = false)
    {
        $query = <<<SQL
UPDATE oro_segment_snapshot set integer_entity_id = CAST(entity_id as int) WHERE entity_id %s '^[0-9]+$';
SQL;
        $function = 'REGEXP';
        if ($this->connection->getDriver()->getName() === DatabaseDriverInterface::DRIVER_POSTGRESQL) {
            $function = '~';
        }
        $query = sprintf($query, $function);

        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->prepare($query)->execute();
        }
    }
}
