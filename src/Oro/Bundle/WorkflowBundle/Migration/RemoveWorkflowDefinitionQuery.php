<?php
declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Removes a specified workflow by its name.
 */
class RemoveWorkflowDefinitionQuery extends ParametrizedMigrationQuery
{
    private string $workflowName;

    public function __construct(string $workflowName)
    {
        $this->workflowName = $workflowName;
    }

    public function getDescription()
    {
        return \sprintf('Removes %s workflow definition.', $this->workflowName);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'DELETE FROM oro_workflow_definition WHERE name = :workflow_name';
        $params = ['workflow_name' => $this->workflowName];
        $types = ['workflow_name' => Types::STRING];
        $this->logQuery($logger, $sql, $params, $types);
        $this->connection->executeStatement($sql, $params, $types);
    }
}
