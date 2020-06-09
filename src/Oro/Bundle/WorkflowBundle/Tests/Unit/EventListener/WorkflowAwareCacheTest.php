<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;

class WorkflowAwareCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var WorkflowAwareCache */
    protected $workflowAwareCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);
        $this->repository = $this->createMock(WorkflowDefinitionRepository::class);

        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->repository);

        $this->workflowAwareCache = new WorkflowAwareCache($this->cache, $doctrineHelper);
    }

    public function testBuild(): void
    {
        $this->repository->expects($this->exactly(2))
            ->method('getAllRelatedEntityClasses')
            ->willReturnMap(
                [
                    [true, [User::class]],
                    [false, [\stdClass::class, User::class]],
                ]
            );

        $this->cache->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [WorkflowAwareCache::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY, [User::class => 0]],
                [WorkflowAwareCache::WORKFLOW_RELATED_CLASSES_KEY, [\stdClass::class => 0, User::class => 1]]
            );

        $this->workflowAwareCache->build();
    }

    public function testInvalidateRelated(): void
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(WorkflowAwareCache::WORKFLOW_RELATED_CLASSES_KEY);

        $this->workflowAwareCache->invalidateRelated();
    }

    public function testInvalidateActiveRelated(): void
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(WorkflowAwareCache::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY);

        $this->workflowAwareCache->invalidateActiveRelated();
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                WorkflowEvents::WORKFLOW_AFTER_UPDATE => [['invalidateRelated'], ['invalidateActiveRelated']],
                WorkflowEvents::WORKFLOW_AFTER_CREATE => [['invalidateRelated'], ['invalidateActiveRelated']],
                WorkflowEvents::WORKFLOW_AFTER_DELETE => [['invalidateRelated'], ['invalidateActiveRelated']],
                WorkflowEvents::WORKFLOW_ACTIVATED => ['invalidateActiveRelated'],
                WorkflowEvents::WORKFLOW_DEACTIVATED => ['invalidateActiveRelated']
            ],
            WorkflowAwareCache::getSubscribedEvents()
        );
    }
}
