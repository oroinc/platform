<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\TestActivityScopeProvider;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTestActivitiesForScopes;
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

        /** @var TestActivity $activity */
        $activity = $this->getReference('test_activity_1');

        self::getContainer()->get('oro_workflow.changes.event.dispatcher')->addListener(
            WorkflowEvents::WORKFLOW_BEFORE_CREATE,
            function (WorkflowChangesEvent $changesEvent) use ($activity) {
                $definition = $changesEvent->getDefinition();
                if ($definition->getName() === 'test_flow_with_scopes') {
                    $definition->setScopesConfig(
                        [
                            [
                                'test_activity' => $activity->getId()
                            ]
                        ]
                    );
                }
            }
        );

        self::loadWorkflowFrom(self::DEFAULT_WORKFLOWS);

        $registry = self::getContainer()->get('oro_workflow.registry');
        $workflow = $registry->getWorkflow('test_flow_with_scopes');
        $scopes = $workflow->getDefinition()->getScopes();
        $this->assertCount(1, $scopes);
    }

    /**
     * @depends testScopesCreated
     */
    public function testScopesUpdated($activityId)
    {
        $this->loadFixtures([LoadTestActivitiesForScopes::class]);

        /** @var TestActivity $activity */
        $activity = $this->getReference('test_activity_1');

        self::getContainer()->get('oro_workflow.changes.event.dispatcher')->addListener(
            WorkflowEvents::WORKFLOW_BEFORE_CREATE,
            function (WorkflowChangesEvent $changesEvent) use ($activity) {
                $definition = $changesEvent->getDefinition();
                if ($definition->getName() === 'test_flow_with_scopes') {
                    $definition->setScopesConfig(
                        [
                            [
                                'test_activity' => $activity->getId()
                            ]
                        ]
                    );
                }
            }
        );

        self::loadWorkflowFrom(self::DEFAULT_WORKFLOWS);

        $registry = self::getContainer()->get('oro_workflow.registry');
        $workflow = $registry->getWorkflow('test_flow_with_scopes');
        $scopes = $workflow->getDefinition()->getScopes();
        $this->assertCount(1, $scopes);
    }
}
