<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

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
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSqlPlatform) {
            $query = 'UPDATE oro_email AS e SET email_body_id = b.id
                FROM oro_email_body AS b WHERE e.id = b.email_id';
        } else {
            $query = 'UPDATE oro_email e LEFT JOIN oro_email_body b
                ON e.id = b.email_id SET e.email_body_id = b.id';
        }

        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->executeStatement($query);
        }
    }
}
