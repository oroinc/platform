<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model\Filter;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTestActivitiesForScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\Environment\TestActivityScopeProvider;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;
use Symfony\Component\HttpFoundation\Request;

class WorkflowDefinitionScopesRegistryFilterTest extends WorkflowTestCase
{
    private const WORKFLOW_SCOPES_CONFIG_DIR = '/Tests/Functional/DataFixtures/WithScopesAndWithout';

    /** @var TestActivityScopeProvider */
    private $activityScopeProvider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadTestActivitiesForScopes::class]);

        $this->activityScopeProvider = self::getContainer()->get('oro_workflow.test_activity_scope_provider');
    }

    protected function tearDown(): void
    {
        $this->activityScopeProvider->setCurrentTestActivity(null);
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

        $this->loadWorkflowFrom(self::WORKFLOW_SCOPES_CONFIG_DIR);

        /* @var WorkflowManager $manager */
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
