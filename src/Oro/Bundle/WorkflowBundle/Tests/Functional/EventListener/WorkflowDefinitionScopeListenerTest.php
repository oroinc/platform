<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTestActivitiesForScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;

class WorkflowDefinitionScopeListenerTest extends WorkflowTestCase
{
    private const WITH_SCOPES_CONFIG_DIR = '/Tests/Functional/DataFixtures/WithScopes';

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTestActivitiesForScopes::class]);
    }

    public function testScopesCreated(): TestActivity
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

        $this->loadWorkflowFrom(self::WITH_SCOPES_CONFIG_DIR);

        $registry = self::getContainer()->get('oro_workflow.registry');
        $workflow = $registry->getWorkflow('test_flow_with_scopes');
        $scopes = $workflow->getDefinition()->getScopes();

        $this->assertCount(1, $scopes);
        $this->assertActivityExists($scopes, $activity);

        return $activity;
    }

    /**
     * @depends testScopesCreated
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

        $this->loadWorkflowFrom(self::WITH_SCOPES_CONFIG_DIR);

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

    private function assertActivityExists(Collection $scopes, TestActivity $activity): void
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
