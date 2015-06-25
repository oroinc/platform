<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_15;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateEmailOriginTableQuery extends ParametrizedMigrationQuery
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

        $dbDriver = $this->connection->getDriver()->getName();
        switch ($dbDriver) {
            case DatabaseDriverInterface::DRIVER_POSTGRESQL:
                $query = <<<SQL
UPDATE oro_email_origin AS eo SET eo.owner_id = ueo.user_id
  FROM oro_user_email_origin AS ueo WHERE ueo.origin_id = eo.id;
UPDATE oro_email_origin AS eo SET eo.organization_id = u.organization_id
  FROM oro_user AS u WHERE u.id = eo.owner_id;
SQL;
                break;
            case DatabaseDriverInterface::DRIVER_MYSQL:
            default:
                $query = <<<SQL
UPDATE oro_email_origin eo SET eo.owner_id =
  (SELECT ueo.user_id FROM oro_user_email_origin ueo WHERE ueo.origin_id = eo.id);
UPDATE oro_email_origin eo SET eo.organization_id =
  (SELECT u.organization_id FROM oro_user u WHERE u.id = eo.owner_id);
SQL;
                break;
        }

        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $this->connection->executeUpdate($query);
        }
    }
}
