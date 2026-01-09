<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\WorkflowBundle\Migration\RemoveWorkflowDefinitionQuery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveWorkflowDefinitionQueryTest extends TestCase
{
    public function testGetDescriptionIncludesWorkflowName(): void
    {
        $workflowName = 'some_workflow_name';
        $query = new RemoveWorkflowDefinitionQuery($workflowName);
        self::assertStringContainsString($workflowName, $query->getDescription());
    }

    public function testExecute(): void
    {
        $workflowName = 'some_workflow_name';
        $query = new RemoveWorkflowDefinitionQuery($workflowName);
        $logger = $this->createMock(LoggerInterface::class);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform());

        $logger->expects(self::exactly(3))
            ->method('info')
            ->withConsecutive(
                ['DELETE FROM oro_workflow_definition WHERE name = :workflow_name', []],
                ['Parameters:', []],
                ['[workflow_name] = ' . $workflowName, []],
            );
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with(
                'DELETE FROM oro_workflow_definition WHERE name = :workflow_name',
                ['workflow_name' => $workflowName],
                ['workflow_name' => Types::STRING]
            );

        $query->setConnection($connection);
        $query->execute($logger);
    }
}
