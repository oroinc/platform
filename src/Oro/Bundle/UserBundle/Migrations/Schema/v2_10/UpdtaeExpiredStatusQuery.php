<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_10;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Psr\Log\LoggerInterface;

/**
 * Update expired user auth_status.
 */
class UpdtaeExpiredStatusQuery extends ParametrizedMigrationQuery
{
    private ExtendExtension $extendExtension;

    public function __construct(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Update expired user auth_status.');
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

    private function doExecute(LoggerInterface $logger, $dryRun = false): void
    {
        $sql = 'UPDATE %s SET id = :id, name = :name WHERE id = :old_id';
        $sql = sprintf($sql, $this->extendExtension->getNameGenerator()->generateEnumTableName('auth_status'));

        $status = [
            ':id' => UserManager::STATUS_RESET,
            ':name' => 'Reset',
            ':old_id' => 'expired'
        ];
        $types = [
            'id' => Types::STRING,
            'name' => Types::STRING,
            'old_id' => Types::STRING,
        ];
        $this->logQuery($logger, $sql, $status, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($sql, $status, $types);
        }

        $sql = 'UPDATE oro_user SET auth_status_id = :id WHERE auth_status_id = :old_id';
        $status = [
            ':id' => UserManager::STATUS_RESET,
            ':old_id' => 'expired'
        ];
        $types = [
            'id' => Types::STRING,
            'old_id' => Types::STRING,
        ];
        $this->logQuery($logger, $sql, $status, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($sql, $status, $types);
        }
    }
}
