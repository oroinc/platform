<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Item2;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\TestActivityScopeProvider;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTestActivitiesForScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionScopes;

class WorkflowDefinitionRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var WorkflowDefinitionRepository */
    protected $repository;

    /** @var ScopeManager */
    protected $scopeManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->em = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(WorkflowDefinition::class);

        $this->repository = $this->em->getRepository(WorkflowDefinition::class);

        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');

        $this->loadFixtures([LoadWorkflowDefinitionScopes::class]);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->repository->invalidateCache();
    }

    /**
     * @param string $class
     * @return string
     */
    private function buildProxyClassName($class)
    {
        return sprintf('Proxies\\%s\\%s', Proxy::MARKER, $class);
    }

    public function testFindActiveForRelatedEntity()
    {
        $class = $this->buildProxyClassName(WorkflowAwareEntity::class);

        $this->assertWorkflowList(
            ['test_active_flow1', 'test_active_flow2'],
            $this->repository->findActiveForRelatedEntity($class)
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
                'test_start_step_flow'
            ],
            $this->repository->findForRelatedEntity($class)
        );
    }

    public function testGetScopedByNames()
    {
        $provider = new TestActivityScopeProvider();
        $provider->setCurrentTestActivity($this->getReference(LoadTestActivitiesForScopes::TEST_ACTIVITY_2));

        $this->registerScopeProvider($provider);

        $workflows = $this->repository->getScopedByNames(
            [
                LoadWorkflowDefinitions::WITH_GROUPS2,
                LoadWorkflowDefinitions::WITH_GROUPS1,
                LoadWorkflowDefinitions::WITH_START_STEP
            ],
            $this->scopeManager->getCriteria(WorkflowScopeManager::SCOPE_TYPE)
        );

        $this->assertCount(1, $workflows);

        $workflow = array_shift($workflows);

        $this->assertEquals(LoadWorkflowDefinitions::WITH_GROUPS1, $workflow->getName());
    }

    /**
     * @param ScopeCriteriaProviderInterface $provider
     */
    protected function registerScopeProvider(ScopeCriteriaProviderInterface $provider)
    {
        $this->scopeManager->addProvider(WorkflowScopeManager::SCOPE_TYPE, $provider);
    }

    public function testFindActive()
    {
        $workflows = $this->getWorkflowNames($this->repository->findActive());

        $this->assertGreaterThanOrEqual(2, count($workflows));
        $this->assertContains('test_active_flow1', $workflows);
        $this->assertContains('test_active_flow2', $workflows);
    }

    public function testGetAllRelatedEntityClasses()
    {
        $result = $this->repository->getAllRelatedEntityClasses();

        $this->assertInternalType('array', $result);
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertContains(WorkflowAwareEntity::class, $result);
        $this->assertContains(Item::class, $result);

        $result = $this->repository->getAllRelatedEntityClasses(true);

        $this->assertInternalType('array', $result);
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertContains(WorkflowAwareEntity::class, $result);
        $this->assertContains(Item::class, $result);
        $this->assertNotContains(Item2::class, $result);
    }

    public function testInvalidateCache()
    {
        $config = $this->em->getConnection()->getConfiguration();
        $oldLogger = $config->getSQLLogger();

        $logger = new DebugStack();
        $config->setSQLLogger($logger);

        $this->repository->findActive();
        $this->assertCount(1, $logger->queries);

        $this->repository->findActive();
        $this->repository->findActive();
        $this->assertCount(1, $logger->queries);

        $this->repository->invalidateCache();
        $this->repository->findActive();
        $this->assertCount(2, $logger->queries);

        $this->repository->findActive();
        $this->repository->findActive();
        $this->assertCount(2, $logger->queries);

        $config->setSQLLogger($oldLogger);
    }

    /**
     * @param array|string[] $expectedWorkflows
     * @param array|WorkflowDefinition[] $workflows
     */
    protected function assertWorkflowList(array $expectedWorkflows, array $workflows)
    {
        $workflows = $this->getWorkflowNames($workflows);

        $this->assertCount(count($expectedWorkflows), $workflows);

        foreach ($expectedWorkflows as $expected) {
            $this->assertContains($expected, $workflows);
        }
    }

    /**
     * @param array|WorkflowDefinition[] $workflows
     * @return array
     */
    protected function getWorkflowNames(array $workflows)
    {
        return array_map(
            function (WorkflowDefinition $definition) {
                return $definition->getName();
            },
            $workflows
        );
    }
}
