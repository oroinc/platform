<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Item2;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTestActivitiesForScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\Environment\TestActivityScopeProvider;

class WorkflowDefinitionRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowDefinitionScopes::class]);
    }

    protected function tearDown(): void
    {
        $this->getActivityScopeProvider()->setCurrentTestActivity(null);
        $this->getRepository()->invalidateCache();
    }

    private function getRepository(): WorkflowDefinitionRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(WorkflowDefinition::class);
    }

    private function getManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(WorkflowDefinition::class);
    }

    private function getScopeManager(): ScopeManager
    {
        return self::getContainer()->get('oro_scope.scope_manager');
    }

    private function getActivityScopeProvider(): TestActivityScopeProvider
    {
        return self::getContainer()->get('oro_workflow.test_activity_scope_provider');
    }

    private function buildProxyClassName(string $class): string
    {
        return sprintf('Proxies\\%s\\%s', Proxy::MARKER, $class);
    }

    /**
     * @param string[] $expectedWorkflows
     * @param WorkflowDefinition[] $workflows
     */
    private function assertWorkflowList(array $expectedWorkflows, array $workflows): void
    {
        $workflows = $this->getWorkflowNames($workflows);

        $this->assertCount(count($expectedWorkflows), $workflows);

        foreach ($expectedWorkflows as $expected) {
            $this->assertContains($expected, $workflows);
        }
    }

    private function getWorkflowNames(array $workflows): array
    {
        return array_map(
            function (WorkflowDefinition $definition) {
                return $definition->getName();
            },
            $workflows
        );
    }

    public function testFindActiveForRelatedEntity()
    {
        $class = $this->buildProxyClassName(WorkflowAwareEntity::class);

        $this->assertWorkflowList(
            ['test_active_flow1', 'test_active_flow2'],
            $this->getRepository()->findActiveForRelatedEntity($class)
        );
    }

    public function testFindForRelatedEntity()
    {
        $class = $this->buildProxyClassName(WorkflowAwareEntity::class);

        $this->assertWorkflowList(
            [
                'test_active_flow1',
                'test_active_flow2',
                'test_flow',
                'test_flow_datagrids',
                'test_groups_flow1',
                'test_groups_flow2',
                'test_multistep_flow',
                'test_start_init_option',
                'test_start_step_flow',
                'test_flow_with_condition',
                'test_flow_transition_with_condition',

            ],
            $this->getRepository()->findForRelatedEntity($class)
        );
    }

    public function testGetScopedByNames()
    {
        $this->getActivityScopeProvider()->setCurrentTestActivity(
            $this->getReference(LoadTestActivitiesForScopes::TEST_ACTIVITY_2)
        );

        $workflows = $this->getRepository()->getScopedByNames(
            [
                LoadWorkflowDefinitions::WITH_GROUPS2,
                LoadWorkflowDefinitions::WITH_GROUPS1,
                LoadWorkflowDefinitions::WITH_START_STEP
            ],
            $this->getScopeManager()->getCriteria(WorkflowScopeManager::SCOPE_TYPE)
        );

        $this->assertCount(1, $workflows);

        $workflow = array_shift($workflows);

        $this->assertEquals(LoadWorkflowDefinitions::WITH_GROUPS1, $workflow->getName());
    }

    public function testFindActive()
    {
        $workflows = $this->getWorkflowNames($this->getRepository()->findActive());

        $this->assertGreaterThanOrEqual(2, count($workflows));
        $this->assertContainsEquals('test_active_flow1', $workflows);
        $this->assertContainsEquals('test_active_flow2', $workflows);
    }

    public function testGetAllRelatedEntityClasses()
    {
        $result = $this->getRepository()->getAllRelatedEntityClasses();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertContainsEquals(WorkflowAwareEntity::class, $result);
        $this->assertContainsEquals(Item::class, $result);

        $result = $this->getRepository()->getAllRelatedEntityClasses(true);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertContainsEquals(WorkflowAwareEntity::class, $result);
        $this->assertContainsEquals(Item::class, $result);
        $this->assertNotContainsEquals(Item2::class, $result);
    }

    public function testInvalidateCache()
    {
        $config = $this->getManager()->getConnection()->getConfiguration();
        $oldLogger = $config->getSQLLogger();

        $logger = new DebugStack();
        $config->setSQLLogger($logger);

        $this->getRepository()->findActive();
        $this->assertCount(1, $logger->queries);

        $this->getRepository()->findActive();
        $this->getRepository()->findActive();
        $this->assertCount(1, $logger->queries);

        $this->getRepository()->invalidateCache();
        $this->getRepository()->findActive();
        $this->assertCount(2, $logger->queries);

        $this->getRepository()->findActive();
        $this->getRepository()->findActive();
        $this->assertCount(2, $logger->queries);

        $config->setSQLLogger($oldLogger);
    }
}
