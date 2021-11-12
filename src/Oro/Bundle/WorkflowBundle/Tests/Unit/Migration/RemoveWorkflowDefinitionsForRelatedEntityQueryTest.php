<?php
declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\WorkflowBundle\Migration\RemoveWorkflowDefinitionsForRelatedEntityQuery;
use Psr\Log\LoggerInterface;

class RemoveWorkflowDefinitionsForRelatedEntityQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetDescriptionIncludesEntityClassName()
    {
        $entityClassName = 'Some\Entity';
        $query = new RemoveWorkflowDefinitionsForRelatedEntityQuery($entityClassName);
        self::assertStringContainsString($entityClassName, $query->getDescription());
    }

    public function testExecute()
    {
        $entityClassName = 'Some\Entity';
        $query = new RemoveWorkflowDefinitionsForRelatedEntityQuery($entityClassName);
        $logger = $this->createMock(LoggerInterface::class);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $logger->expects(self::exactly(3))
            ->method('info')
            ->withConsecutive(
                ['DELETE FROM oro_workflow_definition WHERE related_entity = :entity', []],
                ['Parameters:', []],
                ['[entity] = ' . $entityClassName, []],
            );
        $connection->expects(self::once())
            ->method('executeStatement')
            ->with(
                'DELETE FROM oro_workflow_definition WHERE related_entity = :entity',
                ['entity' => $entityClassName],
                ['entity' => Types::STRING]
            );

        $query->setConnection($connection);
        $query->execute($logger);
    }
}
