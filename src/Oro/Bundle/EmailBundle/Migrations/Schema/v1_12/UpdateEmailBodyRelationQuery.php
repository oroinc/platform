<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateEmailBodyRelationQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $dbDriver = $this->connection->getDriver()->getName();
        switch ($dbDriver) {
            case DatabaseDriverInterface::DRIVER_POSTGRESQL:
                $query = 'UPDATE oro_email AS e SET email_body_id = b.id
                    FROM oro_email_body AS b WHERE e.id = b.email_id';

                break;
            case DatabaseDriverInterface::DRIVER_MYSQL:
            default:
                $query = 'UPDATE oro_email e LEFT JOIN oro_email_body b
                    ON e.id = b.email_id SET e.email_body_id = b.id';

                break;
        }

        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->executeUpdate($query);
        }
    }
}
