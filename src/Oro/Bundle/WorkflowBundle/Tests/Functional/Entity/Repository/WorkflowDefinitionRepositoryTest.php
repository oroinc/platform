<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\TestActivityScopeProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTestActivitiesForScopes;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionScopes;

/**
 * @dbIsolation
 */
class WorkflowDefinitionRepositoryTest extends WebTestCase
{
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

        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(WorkflowDefinition::class)
            ->getRepository(WorkflowDefinition::class);

        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');

        $this->loadFixtures([LoadWorkflowDefinitionScopes::class]);
    }

    public function testFindActiveForRelatedEntity()
    {
        $workflows = array_map(
            function (WorkflowDefinition $definition) {
                return ['name' => $definition->getName()];
            },
            $this->repository->findActiveForRelatedEntity(WorkflowAwareEntity::class)
        );

        $this->assertSame([
            ['name' => 'test_active_flow1'],
            ['name' => 'test_active_flow2'],
        ], $workflows);
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
        $workflows = array_map(
            function (WorkflowDefinition $definition) {
                return ['name' => $definition->getName()];
            },
            $this->repository->findActive()
        );
        $this->assertGreaterThanOrEqual(2, count($workflows));
        $this->assertContains(['name' => 'test_active_flow1'], $workflows);
        $this->assertContains(['name' => 'test_active_flow2'], $workflows);
    }
}
