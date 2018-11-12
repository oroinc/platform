<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\ArrayCache;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;

class WorkflowAwareCacheInvalidationTest extends \PHPUnit\Framework\TestCase
{
    /** @var ArrayCache */
    protected $cache;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var WorkflowDefinitionRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $definitionRepository;

    /** @var WorkflowAwareCache */
    protected $workflowAwareCache;

    /** @var \stdClass */
    private $entity;

    protected function setUp()
    {
        $this->cache = new ArrayCache();
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
        $this->definitionRepository->expects($this->exactly(3))
            ->method('getAllRelatedEntityClasses')
            ->withConsecutive([true], [true], [true])
            ->willReturnOnConsecutiveCalls(
                [\stdClass::class, \DateTime::class],
                [\stdClass::class],
                [\DateTime::class]
            );

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedActiveWorkflows($this->entity),
            'Must be matched by fetched from db'
        );

        $this->assertTrue(
            $this->cache->contains(WorkflowAwareCache::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY),
            'Data must be retrieved and cached.'
        );

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedActiveWorkflows($this->entity),
            'Result must be the same. Matched by fetched from cache.'
        );

        //first invalidation. \DateTime::class were removed from repository result
        $this->workflowAwareCache->invalidateActiveRelated();

        $this->assertFalse(
            $this->cache->contains(WorkflowAwareCache::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY),
            'Cache must be destroyed and do not contains a data under the key'
        );

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedActiveWorkflows($this->entity),
            'Must be matched by fetched from db'
        );

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedActiveWorkflows($this->entity),
            'Result must be the same. Matched by fetched from cache.'
        );

        //second invalidation. \stdClass were removed from repository result \DateTime added
        $this->workflowAwareCache->invalidateActiveRelated();

        $this->assertFalse(
            $this->cache->contains(WorkflowAwareCache::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY),
            'Cache must be destroyed and do not contains a data under the key'
        );

        $this->assertFalse(
            $this->workflowAwareCache->hasRelatedActiveWorkflows($this->entity),
            'Must be matched by fetched from db as class not in set'
        );

        $this->assertTrue(
            $this->cache->contains(WorkflowAwareCache::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY),
            'Data must be cached.'
        );

        $this->assertFalse(
            $this->workflowAwareCache->hasRelatedActiveWorkflows($this->entity),
            'Result must be the same. Not matched by fetched from cache.'
        );
    }

    public function testInvalidationOfWorkflowRelatedEntityClassesList()
    {
        $this->definitionRepository->expects($this->exactly(3))
            ->method('getAllRelatedEntityClasses')
            ->withConsecutive([false], [false], [false])
            ->willReturnOnConsecutiveCalls(
                [\stdClass::class, \DateTime::class],
                [\stdClass::class],
                [\DateTime::class]
            );

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedWorkflows($this->entity),
            'Must be matched by fetched from db'
        );

        $this->assertTrue(
            $this->cache->contains(WorkflowAwareCache::WORKFLOW_RELATED_CLASSES_KEY),
            'Data must be retrieved and cached.'
        );

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedWorkflows($this->entity),
            'Result must be the same. Matched by fetched from cache.'
        );

        //first invalidation. \DateTime::class were removed from repository result
        $this->workflowAwareCache->invalidateRelated();

        $this->assertFalse(
            $this->cache->contains(WorkflowAwareCache::WORKFLOW_RELATED_CLASSES_KEY),
            'Cache must be destroyed and do not contains a data under the key'
        );

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedWorkflows($this->entity),
            'Must be matched by fetched from db'
        );

        $this->assertTrue(
            $this->cache->contains(WorkflowAwareCache::WORKFLOW_RELATED_CLASSES_KEY),
            'Cache must be destroyed and do not contains a data under the key'
        );

        $this->assertTrue(
            $this->workflowAwareCache->hasRelatedWorkflows($this->entity),
            'Result must be the same. Matched by fetched from cache.'
        );

        //second invalidation. \stdClass were removed from repository result \DateTime added
        $this->workflowAwareCache->invalidateRelated();

        $this->assertFalse(
            $this->cache->contains(WorkflowAwareCache::WORKFLOW_RELATED_CLASSES_KEY),
            'Cache must be destroyed and do not contains a data under the key'
        );

        $this->assertFalse(
            $this->workflowAwareCache->hasRelatedWorkflows($this->entity),
            'Must be matched by fetched from db as class not in set'
        );

        $this->assertTrue(
            $this->cache->contains(WorkflowAwareCache::WORKFLOW_RELATED_CLASSES_KEY),
            'Data must be cached.'
        );

        $this->assertFalse(
            $this->workflowAwareCache->hasRelatedWorkflows($this->entity),
            'Result must be the same. Not matched by fetched from cache.'
        );
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
