<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WorkflowAwareCacheTest extends \PHPUnit\Framework\TestCase
{
    private const ACTIVE_WORKFLOW_RELATED_CLASSES_KEY = 'active_workflow_related';
    private const WORKFLOW_RELATED_CLASSES_KEY = 'all_workflow_related';

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var WorkflowAwareCache */
    private $workflowAwareCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->repository = $this->createMock(WorkflowDefinitionRepository::class);

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
            ->willReturnMap([
                [true, [User::class]],
                [false, [\stdClass::class, User::class]],
            ]);

        $this->cache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY],
                [self::WORKFLOW_RELATED_CLASSES_KEY]
            )->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->workflowAwareCache->build();
    }

    public function testInvalidateRelated(): void
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::WORKFLOW_RELATED_CLASSES_KEY);

        $this->workflowAwareCache->invalidateRelated();
    }

    public function testInvalidateActiveRelated(): void
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY);

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
