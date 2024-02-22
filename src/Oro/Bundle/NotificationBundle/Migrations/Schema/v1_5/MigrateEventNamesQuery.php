<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_5;

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
        $query = 'UPDATE oro_notification_email_notif AS n 
                    SET event_name = e.name
                    FROM oro_notification_event AS e
                    WHERE event_id = e.id ';
        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->executeStatement($query);
        }
    }
}
