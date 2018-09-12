<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Add comment to oro_cron_schedule.args field on Postgres
 */
class SetCommentOnJsonArrayQuery extends ParametrizedMigrationQuery
{
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            'Set missing doctrine type hint comment on json_array field.'
        );
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
     * {@inheritdoc}
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQL92Platform) {
            $commentSql = "COMMENT ON COLUMN oro_cron_schedule.args IS '(DC2Type:json_array)'";
            $this->logQuery($logger, $commentSql);

            if (!$dryRun) {
                $this->connection->executeUpdate($commentSql);
            }
        }
    }
}
