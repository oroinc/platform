<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_15;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class FillEmailUserTableQuery extends ParametrizedMigrationQuery
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
     * @param bool $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $query = <<<SQL
INSERT INTO oro_email_user (folder_id, email_id, created_at, received, is_seen, user_owner_id, organization_id)
SELECT f.id, e.id, e.created, e.received, e.is_seen, o.owner_id, o.organization_id
FROM oro_email_to_folder etf
  LEFT JOIN oro_email e ON e.id = etf.email_id
  LEFT JOIN oro_email_folder f ON f.id =  etf.emailfolder_id
  LEFT JOIN oro_email_origin o ON o.id = f.origin_id
SQL;

        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->executeUpdate($query);
        }
    }
}
