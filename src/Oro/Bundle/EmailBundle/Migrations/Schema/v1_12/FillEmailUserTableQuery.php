<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

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

    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $query = <<<SQL
INSERT INTO oro_email_user (folder_id, email_id, created_at, received, is_seen)
  SELECT f.emailfolder_id, e.id, e.created, e.received, e.is_seen
  FROM oro_email e left join oro_email_to_folder f on f.email_id = e.id
SQL;

        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->executeUpdate($query);
        }
    }
}
