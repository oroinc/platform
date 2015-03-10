<?php

namespace Oro\Bundle\TrackingBundle\Migrations\Schema\v1_6;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

/**
 * This query sets parsed column data for oro_tracking_event table to false
 */
class UpdateTrackingEventQuery  implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'UPDATE oro_tracking_event SET parsed = false';
        $logger->notice($query);
        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }
}
