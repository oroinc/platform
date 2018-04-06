<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model\Filter;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\TestActivityScopeProvider;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTestActivitiesForScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;
use Symfony\Component\HttpFoundation\Request;

class WorkflowDefinitionScopesRegistryFilterTest extends WorkflowTestCase
{
    const WORKFLOW_SCOPES_CONFIG_DIR = '/Tests/Functional/DataFixtures/WithScopesAndWithout';

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

    public function testFilter()
    {
        $this->updateUserSecurityToken(self::AUTH_USER);
        $this->getContainer()->get('request_stack')->push(new Request());

        /** @var TestActivity $initialActivity */
        $initialActivity = $this->getReference('test_activity_1');

        self::getContainer()->get('oro_workflow.changes.event.dispatcher')->addListener(
            WorkflowEvents::WORKFLOW_BEFORE_CREATE,
            function (WorkflowChangesEvent $changesEvent) use ($initialActivity) {
                $definition = $changesEvent->getDefinition();
                if ($definition->getName() === 'test_flow_with_scopes') {
                    $definition->setScopesConfig(
                        [
                            [
                                'test_activity' => $initialActivity->getId()
                            ]
                        ]
                    );
                }
            }
        );

        self::loadWorkflowFrom(self::WORKFLOW_SCOPES_CONFIG_DIR);

        /* @var $manager WorkflowManager */
        $manager = self::getContainer()->get('oro_workflow.manager');

        $this->activityScopeProvider->setCurrentTestActivity($initialActivity);

        $workflows = array_keys($manager->getApplicableWorkflows(WorkflowAwareEntity::class));

        $expectedWorkflows = ['test_flow_with_scopes', 'test_flow_without_scopes'];

        $this->assertEmpty(array_diff($expectedWorkflows, $workflows));

        //changing context
        $this->activityScopeProvider->setCurrentTestActivity($this->getReference('test_activity_2'));

        $workflows = array_keys($manager->getApplicableWorkflows(WorkflowAwareEntity::class));

        $expectedWorkflows = ['test_flow_without_scopes'];

        $this->assertEmpty(array_diff($expectedWorkflows, $workflows));
        $this->assertNotContains('test_flow_with_scopes', $workflows);
    }
}
