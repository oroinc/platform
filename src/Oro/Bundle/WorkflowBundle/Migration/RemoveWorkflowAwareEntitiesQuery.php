<?php
declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Removes instances of entity created from the specified workflow.
 */
class RemoveWorkflowAwareEntitiesQuery extends ParametrizedMigrationQuery
{
    private string $workflowName;
    private string $className;
    private string $tableName;

    public function __construct(string $workflowName, string $className, string $tableName)
    {
        $this->workflowName = $workflowName;
        $this->className = $className;
        $this->tableName = $tableName;
    }

    public function getDescription(): string
    {
        return \sprintf(
            'Removes instances of %s entity created from the %s workflow.',
            $this->className,
            $this->workflowName
        );
    }

    public function execute(LoggerInterface $logger): void
    {
        $qb = $this->connection->createQueryBuilder();
        $sql = $qb->delete($this->tableName, 'e')
            ->where($qb->expr()->in('e.id', ':ids'))
            ->getSQL();

        $params = ['ids' => $this->getIds($logger)];
        $types = ['ids' => Connection::PARAM_INT_ARRAY];

        $this->logQuery($logger, $sql, $params, $types);
        $this->connection->executeStatement($sql, $params, $types);
    }

    private function getIds(LoggerInterface $logger): array
    {
        $qb = $this->connection->createQueryBuilder();
        $dbDriver = $this->connection->getDriver()->getName();
        $condition = match ($dbDriver) {
            DatabaseDriverInterface::DRIVER_MYSQL => $qb->expr()->andX(
                $qb->expr()->eq('CAST(wi.entity_id as unsigned integer)', 'e.id'),
                $qb->expr()->eq('wi.entity_class', ':class_name')
            ),
            default => $qb->expr()->andX(
                $qb->expr()->eq('CAST(wi.entity_id as integer)', 'e.id'),
                $qb->expr()->eq('wi.entity_class', ':class_name')
            ),
        };

        $sql = $qb->select('e.id')
            ->from($this->tableName, 'e')
            ->innerJoin(
                'e',
                'oro_workflow_item',
                'wi',
                $condition
            )
            ->where($qb->expr()->eq('wi.workflow_name', ':workflow_name'))
            ->getSQL();

        $params = ['class_name' => $this->className, 'workflow_name' => $this->workflowName];
        $types = ['class_name' => Types::STRING, 'workflow_name' => Types::STRING];

        $this->logQuery($logger, $sql, $params, $types);
        return array_column($this->connection->fetchAll($sql, $params, $types), 'id');
    }
}
