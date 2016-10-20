<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_23;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class MigrateUserPasswords extends ParametrizedSqlMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
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
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $this->insertUserPasswords($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function insertUserPasswords(LoggerInterface $logger, $dryRun)
    {
        $slq =
            'INSERT INTO oro_user_password_hash'
            . ' (user_id, salt, hash, created_at)'
            . ' SELECT id, salt, password, :now'
            . ' FROM oro_user';

        $params = ['now' => new \DateTime()];
        $types = ['now' => Type::DATETIME];

        $this->logQuery($logger, $slq, $params, $types);

        if (!$dryRun) {
            $this->connection->executeUpdate($slq, $params, $types);
        }
    }
}
