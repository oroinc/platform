<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Provider\RunningWorkflowProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RunningWorkflowProviderTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new RunningWorkflowProvider($this->doctrineHelper);
    }

    public function testGetRunningWorkflowNames(): void
    {
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entityId = 123;
        $workflowNames = ['workflow1', 'workflow2'];

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityClass')
            ->with(self::identicalTo($entity))
            ->willReturn($entityClass);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn($entityId);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(WorkflowItem::class, 'wi')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('select')
            ->with('wi.workflowName')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('wi.entityClass = :entityClass AND wi.entityId = :entityId')
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['entityClass', $entityClass], ['entityId', (string)$entityId])
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getSingleColumnResult')
            ->willReturn($workflowNames);

        self::assertEquals($workflowNames, $this->provider->getRunningWorkflowNames($entity));
        // test memory cache
        self::assertEquals($workflowNames, $this->provider->getRunningWorkflowNames($entity));
    }

    public function testGetRunningWorkflowNamesForDifferentEntities(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $entityClass = \stdClass::class;
        $entity1Id = 123;
        $entity2Id = 234;
        $workflowNames1 = ['workflow1', 'workflow2'];
        $workflowNames2 = ['workflow3', 'workflow4'];

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityClass')
            ->withConsecutive([self::identicalTo($entity1)], [self::identicalTo($entity2)])
            ->willReturn($entityClass);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getSingleEntityIdentifier')
            ->withConsecutive([self::identicalTo($entity1)], [self::identicalTo($entity2)])
            ->willReturnOnConsecutiveCalls($entity1Id, $entity2Id);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with(WorkflowItem::class, 'wi')
            ->willReturn($qb);
        $qb->expects(self::exactly(2))
            ->method('select')
            ->with('wi.workflowName')
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('where')
            ->with('wi.entityClass = :entityClass AND wi.entityId = :entityId')
            ->willReturnSelf();
        $qb->expects(self::exactly(4))
            ->method('setParameter')
            ->withConsecutive(
                ['entityClass', $entityClass],
                ['entityId', (string)$entity1Id],
                ['entityClass', $entityClass],
                ['entityId', (string)$entity2Id]
            )
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::exactly(2))
            ->method('getSingleColumnResult')
            ->willReturnOnConsecutiveCalls($workflowNames1, $workflowNames2);

        self::assertEquals($workflowNames1, $this->provider->getRunningWorkflowNames($entity1));
        self::assertEquals($workflowNames2, $this->provider->getRunningWorkflowNames($entity2));
    }

    public function testReset(): void
    {
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entityId = 123;
        $workflowNames = ['workflow1', 'workflow2'];

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityClass')
            ->with(self::identicalTo($entity))
            ->willReturn($entityClass);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getSingleEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn($entityId);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->with(WorkflowItem::class, 'wi')
            ->willReturn($qb);
        $qb->expects(self::exactly(2))
            ->method('select')
            ->with('wi.workflowName')
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('where')
            ->with('wi.entityClass = :entityClass AND wi.entityId = :entityId')
            ->willReturnSelf();
        $qb->expects(self::exactly(4))
            ->method('setParameter')
            ->withConsecutive(
                ['entityClass', $entityClass],
                ['entityId', (string)$entityId],
                ['entityClass', $entityClass],
                ['entityId', (string)$entityId]
            )
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::exactly(2))
            ->method('getSingleColumnResult')
            ->willReturn($workflowNames);

        self::assertEquals($workflowNames, $this->provider->getRunningWorkflowNames($entity));

        $this->provider->reset();
        self::assertEquals($workflowNames, $this->provider->getRunningWorkflowNames($entity));
    }
}
