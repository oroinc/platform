<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_3;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
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
UPDATE oro_segment_snapshot set integer_entity_id = CAST(entity_id as %s) WHERE entity_id %s '^[0-9]+$';
SQL;
        $type = 'UNSIGNED';
        $function = 'REGEXP';
        if ($this->connection->getDriver()->getName() === DatabaseDriverInterface::DRIVER_POSTGRESQL) {
            $function = '~';
            $type = 'int';
        }
        $query = sprintf($query, $type, $function);

        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->prepare($query)->execute();
        }
    }
}
