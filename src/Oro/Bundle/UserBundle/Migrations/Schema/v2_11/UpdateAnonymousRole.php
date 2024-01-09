<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_11;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Psr\Log\LoggerInterface;

class UpdateAnonymousRole extends ParametrizedMigrationQuery implements Migration
{
    const IS_AUTHENTICATED_ANONYMOUSLY  = 'IS_AUTHENTICATED_ANONYMOUSLY';
    const PUBLIC_ACCESS  = 'PUBLIC_ACCESS';

    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'UPDATE oro_access_role SET role = :role WHERE role = :old_role';
        $parameters = [
            'old_role' => self::IS_AUTHENTICATED_ANONYMOUSLY,
            'role' => self::PUBLIC_ACCESS
        ];
        $types = ['old_role' => Types::STRING, 'role' => Types::STRING];

        $this->logQuery($logger, $sql, $parameters, $types);

        if (!$dryRun) {
            $this->connection->executeStatement($sql, $parameters, $types);
        }
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new self());
    }
}
