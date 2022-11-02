<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_5;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Migrate data from event table to email notification table
 */
class MigrateEventNamesQuery extends ParametrizedMigrationQuery
{
    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * @inheritDoc
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $dbDriver = $this->connection->getDriver()->getName();
        switch ($dbDriver) {
            case DatabaseDriverInterface::DRIVER_POSTGRESQL:
                $query = 'UPDATE oro_notification_email_notif AS n 
                    SET event_name = e.name
                    FROM oro_notification_event AS e
                    WHERE event_id = e.id ';
                break;
            case DatabaseDriverInterface::DRIVER_MYSQL:
            default:
                $query = 'UPDATE oro_notification_email_notif AS n
                    JOIN oro_notification_event AS e ON n.event_id = e.id
                    SET n.event_name = e.name';
                break;
        }
        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->executeStatement($query);
        }
    }
}
