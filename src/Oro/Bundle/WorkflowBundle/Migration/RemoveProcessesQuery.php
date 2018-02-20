<?php

namespace Oro\Bundle\WorkflowBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveProcessesQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $names = [];

    /**
     * @param string|string[] $names
     */
    public function __construct($names)
    {
        $this->names = (array) $names;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Removes visibility processes from all "process" tables';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = "DELETE FROM oro_process_definition WHERE name in (?)";

        $this->connection->executeQuery(
            $sql,
            [$this->names],
            [\Doctrine\Dbal\Connection::PARAM_STR_ARRAY]
        );

        $logger->debug($sql, $this->names);
    }
}
