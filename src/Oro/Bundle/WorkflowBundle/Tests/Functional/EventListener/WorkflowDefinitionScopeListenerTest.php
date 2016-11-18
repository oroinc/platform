<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ScopeBundle\Entity\Scope;
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
    const WITH_SCOPES_CONFIG_DIR = '/Tests/Functional/DataFixtures/WithScopes';

    /**
     * @var TestActivityScopeProvider
     */
    private $activityScopeProvider;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadTestActivitiesForScopes::class]);
        $this->activityScopeProvider = new TestActivityScopeProvider();
        self::getContainer()->get('oro_scope.scope_manager')
            ->addProvider('workflow_definition', $this->activityScopeProvider);
    }

    /**
     * @return TestActivity
     */
    public function testScopesCreated()
    {
        /** @var TestActivity $activity */
        $activity = $this->getReference('test_activity_1');

        self::getContainer()
            ->get('oro_workflow.changes.event.dispatcher')
            ->addListener(
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

        self::loadWorkflowFrom(self::WITH_SCOPES_CONFIG_DIR);

        $registry = self::getContainer()->get('oro_workflow.registry');
        $workflow = $registry->getWorkflow('test_flow_with_scopes');
        $scopes = $workflow->getDefinition()->getScopes();

        $this->assertCount(1, $scopes);
        $this->assertActivityExists($scopes, $activity);

        return $activity;
    }

    /**
     * @depends testScopesCreated
     * @param TestActivity $previousActivity
     */
    public function testScopesUpdated(TestActivity $previousActivity)
    {
        /** @var TestActivity $activity */
        $activity2 = $this->getReference('test_activity_2');
        $activity3 = $this->getReference('test_activity_3');

        self::getContainer()->get('oro_workflow.changes.event.dispatcher')->addListener(
            WorkflowEvents::WORKFLOW_BEFORE_UPDATE,
            function (WorkflowChangesEvent $changesEvent) use ($activity2, $activity3) {
                $definition = $changesEvent->getDefinition();
                if ($definition->getName() === 'test_flow_with_scopes') {
                    $definition->setScopesConfig(
                        [
                            [
                                'test_activity' => $activity2->getId()
                            ],
                            [
                                'test_activity' => $activity3->getId()
                            ]
                        ]
                    );
                }
            }
        );

        self::loadWorkflowFrom(self::WITH_SCOPES_CONFIG_DIR);

        $registry = self::getContainer()->get('oro_workflow.registry');
        $workflow = $registry->getWorkflow('test_flow_with_scopes');
        $scopes = $workflow->getDefinition()->getScopes();

        $this->assertCount(2, $scopes);
        $this->assertActivityExists($scopes, $activity2);
        $this->assertActivityExists($scopes, $activity3);
        $this->assertTrue(
            $scopes->forAll(
                function ($key, Scope $scope) use ($previousActivity) {
                    return $scope->getTestActivity()->getId() !== $previousActivity->getId();
                }
            )
        );
    }

    /**
     * @param Collection $scopes
     * @param TestActivity $activity
     */
    protected function assertActivityExists(Collection $scopes, TestActivity $activity)
    {
        $this->assertTrue(
            $scopes->exists(
                function ($key, Scope $scope) use ($activity) {
                    return $scope->getTestActivity()->getId() === $activity->getId();
                }
            )
        );
    }
}
