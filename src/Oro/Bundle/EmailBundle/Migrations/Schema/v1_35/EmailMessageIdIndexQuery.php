<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_35;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Adds DB index for column `message_id`
 */
class EmailMessageIdIndexQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Create additional expression index on PostgreSQL');
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

    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $createIndex = 'CREATE INDEX IDX_email_message_id ON oro_email (message_id)';
        $this->logQuery($logger, $createIndex);
        if (!$dryRun) {
            $this->connection->executeStatement($createIndex);
        }
    }
}
