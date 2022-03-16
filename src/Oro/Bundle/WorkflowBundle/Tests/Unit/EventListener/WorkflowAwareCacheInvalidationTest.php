<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WorkflowAwareCacheInvalidationTest extends \PHPUnit\Framework\TestCase
{
    private const ACTIVE_WORKFLOW_RELATED_CLASSES_KEY = 'active_workflow_related';
    private const WORKFLOW_RELATED_CLASSES_KEY = 'all_workflow_related';

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $definitionRepository;

    /** @var WorkflowAwareCache */
    protected $workflowAwareCache;

    /** @var \stdClass */
    private $entity;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->entity = new \stdClass();
        $this->definitionRepository = $this->createMock(WorkflowDefinitionRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects($this->any())->method('getEntityClass')->willReturn(get_class($this->entity));
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->definitionRepository);

        $this->workflowAwareCache = new WorkflowAwareCache($this->cache, $this->doctrineHelper);
    }

    public function testInvalidationOfActiveWorkflowRelatedEntityClassesList()
    {
        $this->definitionRepository->expects($this->once())
            ->method('getAllRelatedEntityClasses')
            ->withConsecutive([true], [true], [true])
            ->willReturnOnConsecutiveCalls(
                [\stdClass::class, \DateTime::class],
                [\stdClass::class],
                [\DateTime::class]
            );
        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedActiveWorkflows($this->entity),
            'Must be matched by fetched from db'
        );

        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY);

        $this->workflowAwareCache->invalidateActiveRelated();
    }

    public function testInvalidationOfWorkflowRelatedEntityClassesList()
    {
        $this->definitionRepository->expects($this->once())
            ->method('getAllRelatedEntityClasses')
            ->withConsecutive([false], [false], [false])
            ->willReturnOnConsecutiveCalls(
                [\stdClass::class, \DateTime::class],
                [\stdClass::class],
                [\DateTime::class]
            );

        $this->cache->expects(self::once())
            ->method('get')
            ->with(self::WORKFLOW_RELATED_CLASSES_KEY)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedWorkflows($this->entity),
            'Must be matched by fetched from db'
        );

        $this->cache->expects($this->once())
            ->method('delete')
            ->with(self::WORKFLOW_RELATED_CLASSES_KEY);
        $this->workflowAwareCache->invalidateRelated();
    }

    public function testGetSubscribedEvents()
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

    /**
     * Due to possibility to cover with functional test only one case
     */
    public function testDeletionTriggersSameMethodsAsCreateOrUpdate()
    {
        $subscribedEvents = WorkflowAwareCache::getSubscribedEvents();

        $this->assertEquals(
            $subscribedEvents[WorkflowEvents::WORKFLOW_AFTER_UPDATE],
            $subscribedEvents[WorkflowEvents::WORKFLOW_AFTER_DELETE]
        );

        $this->assertEquals(
            $subscribedEvents[WorkflowEvents::WORKFLOW_AFTER_UPDATE],
            $subscribedEvents[WorkflowEvents::WORKFLOW_AFTER_CREATE]
        );
    }
}
