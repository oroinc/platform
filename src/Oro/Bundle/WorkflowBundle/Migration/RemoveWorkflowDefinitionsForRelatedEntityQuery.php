<?php
declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Removes all workflows for the specified related entity class.
 */
class RemoveWorkflowDefinitionsForRelatedEntityQuery extends ParametrizedMigrationQuery
{
    private string $entityClassName;

    public function __construct(string $entityClassName)
    {
        $this->entityClassName = $entityClassName;
    }

    public function getDescription()
    {
        return \sprintf('Removes all workflow definitions for %s related entity.', $this->entityClassName);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'DELETE FROM oro_workflow_definition WHERE related_entity = :entity';
        $params = ['entity' => $this->entityClassName];
        $types = ['entity' => Types::STRING];
        $this->logQuery($logger, $sql, $params, $types);
        $this->connection->executeStatement($sql, $params, $types);
    }
}
