<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\TestActivityScopeProvider;
use Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener\DataFixtures\LoadTestActivitiesForScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;

/**
 * @dbIsolation
 */
class WorkflowDefinitionScopeListenerTest extends WorkflowTestCase
{
    const DEFAULT_WORKFLOWS = '/Tests/Functional/EventListener/DataFixtures/WithScopesDefault';
    const UPDATED_WORKFLOWS = '/Tests/Functional/EventListener/DataFixtures/WithScopesUpdated';

    /**
     * @var TestActivityScopeProvider
     */
    private $activityScopeProvider;

    protected function setUp()
    {
        $this->initClient();
        $this->activityScopeProvider = new TestActivityScopeProvider();
        self::getContainer()->get('oro_scope.scope_manager')
            ->addProvider('workflow_definition', $this->activityScopeProvider);
    }

    public function testScopesCreated()
    {
        $this->loadFixtures([LoadTestActivitiesForScopes::class]);

        self::loadWorkflowFrom(self::DEFAULT_WORKFLOWS);

        $activity = $this->getReference('test_activity_1');

        $this->assertInstanceOf(TestActivity::class, $activity);

        $registry = self::getContainer()->get('oro_workflow.registry');
        $workflow = $registry->getWorkflow('test_flow_with_scopes');
        $scopes = $workflow->getDefinition()->getScopes();
        $this->assertCount(1, $scopes);
    }
}
