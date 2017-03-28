<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;

class WorkflowAwareCacheTest extends WorkflowTestCase
{
    /**
     * @var \Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache
     */
    private $cache;

    protected function setUp()
    {
        $this->initClient([]);
        $this->cache = self::getContainer()->get('oro_workflow.cache.entity_aware');
    }

    protected function tearDown()
    {
        $this->cache->invalidateRelated();
        $this->cache->invalidateActiveRelated();
    }

    public function testInvalidation()
    {
        //simulate cache isolation present in system - force clearing workflow cache before test
        $this->cache->invalidateActiveRelated();
        $this->cache->invalidateRelated();

        $workflowRegistry = self::getSystemWorkflowRegistry();
        $workflowManager = self::getSystemWorkflowManager();

        self::assertFalse(
            $workflowRegistry->hasActiveWorkflowsByEntityClass(WorkflowAwareEntity::class),
            'Test workflow must not be loaded.'
        );

        //no related workflow present
        self::assertFalse($this->cache->hasRelatedWorkflows(WorkflowAwareEntity::class));
        self::assertFalse($this->cache->hasRelatedActiveWorkflows(WorkflowAwareEntity::class));

        //covering invalidation on new workflow created. Same behavior of update and delete are covered by unit tests.
        self::loadWorkflowFrom('/Tests/Functional/EventListener/DataFixtures/config/AwareCache');

        self::assertTrue($workflowRegistry->hasActiveWorkflowsByEntityClass(WorkflowAwareEntity::class));
        self::assertTrue($this->cache->hasRelatedWorkflows(WorkflowAwareEntity::class));
        self::assertTrue($this->cache->hasRelatedActiveWorkflows(WorkflowAwareEntity::class));

        $workflowManager->deactivateWorkflow('test_flow_aware');

        self::assertFalse(
            $workflowRegistry->hasActiveWorkflowsByEntityClass(WorkflowAwareEntity::class),
            'workflow should be disabled'
        );
        self::assertTrue(
            $this->cache->hasRelatedWorkflows(WorkflowAwareEntity::class),
            'Relation must be still present.'
        );

        self::assertFalse(
            $this->cache->hasRelatedActiveWorkflows(WorkflowAwareEntity::class),
            'Active workflows for entity should not be present. Cache rebuilt.'
        );
    }
}
