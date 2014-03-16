<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Doctrine\DBAL\Connection;

use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

use Psr\Log\LoggerInterface;

class UpdateExtendIndexMigrationQuery implements MigrationQuery
{
    /** @var  string[] */
    protected $queries = [];

    public function __construct($queries)
    {
        $this->queries = $queries;
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        foreach ($this->queries as $query) {
            $logger->notice($query);
        }

        return $logger->getMessages();
    }

    /**
     * @inheritdoc
     */
    public function execute(Connection $connection, LoggerInterface $logger)
    {
        foreach ($this->queries as $query) {
            $logger->notice($query);
            $connection->executeQuery($query);
        }
    }
}
