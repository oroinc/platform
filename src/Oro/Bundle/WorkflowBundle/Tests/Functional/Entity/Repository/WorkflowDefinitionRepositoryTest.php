<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
class WorkflowDefinitionRepositoryTest extends WebTestCase
{
    /** @var WorkflowDefinitionRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(WorkflowDefinition::class)
            ->getRepository(WorkflowDefinition::class);

        $this->loadFixtures([LoadWorkflowDefinitions::class]);
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
}
