<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_28;


use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateBodyQuery extends ParametrizedMigrationQuery
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
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $updatePlainBodySQL ='UPDATE oro_email_body SET text_body = body '
            . 'WHERE body_is_text = true AND body IS NOT NULL;';

        $this->logQuery($logger, $updatePlainBodySQL);
        if (!$dryRun) {
            $this->connection->executeUpdate($updatePlainBodySQL);
        }
    }
}
