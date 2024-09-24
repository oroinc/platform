<?php

namespace Oro\Bundle\WorkflowBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * The migration query that removes visibility processes from all "process" tables.
 */
class RemoveProcessesQuery implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

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

    #[\Override]
    public function getDescription()
    {
        return 'Removes visibility processes from all "process" tables';
    }

    #[\Override]
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
