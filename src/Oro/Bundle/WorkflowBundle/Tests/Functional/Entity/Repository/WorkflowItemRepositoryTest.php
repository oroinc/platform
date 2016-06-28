<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntities;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class WorkflowItemRepositoryTest extends WebTestCase
{
    /**
     * @var WorkflowItemRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntities']);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroWorkflowBundle:WorkflowItem');
    }

    public function testFindByEntityMetadata()
    {
        /** @var WorkflowAwareEntity $entity */
        $entity = $this->getReference('workflow_aware_entity.15');

        /** @var WorkflowItem $item */
        $item = $this->getReference('workflow_item.15');

        $actual = $this->repository->findByEntityMetadata(
            'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            $entity->getId()
        );

        $this->assertNotEmpty($actual);
    }

    public function testFindAllByEntityMetadata()
    {
        $entityIds = $this->getEntityIdsByWorkflow();
        $entityId = reset($entityIds[LoadWorkflowDefinitions::NO_START_STEP]);
        $this->assertInternalType(
            'array',
            $this->repository->findAllByEntityMetadata(
                'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
                $entityId
            )
        );
    }

    public function testFindOneByEntityMetadata()
    {
        $entityIds = $this->getEntityIdsByWorkflow();
        $entityId = reset($entityIds[LoadWorkflowDefinitions::NO_START_STEP]);

        $this->assertNull($this->repository->findOneByEntityMetadata(
                'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
                $entityId,
                'SOME_NON_EXISTING_WORKFLOW'
            )
        );

        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
            $this->repository->findOneByEntityMetadata(
                'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
                $entityId,
                LoadWorkflowDefinitions::NO_START_STEP
            )
        );
    }

    public function testGetGroupedWorkflowNameAndWorkflowStepName()
    {
        $entities = [$this->getReference('workflow_aware_entity.15')];
        $entities[] = $this->getReference('workflow_aware_entity.16');

        $workflowNames = [
            LoadWorkflowDefinitions::WITH_START_STEP,
            LoadWorkflowDefinitions::MULTISTEP,
            LoadWorkflowDefinitions::NO_START_STEP
        ];
        $expectedData = [];

        /** @var WorkflowAwareEntity $entity */
        foreach ($entities as $entity) {
            foreach ($workflowNames as $workflowName) {
                /** @var WorkflowItem $workflowItem */
                $workflowItem = $this->getContainer()->get('oro_workflow.manager')->getWorkflowItem($entity, $workflowName);
                $expectedData[$entity->getId()][] = [
                    'entityId' => $entity->getId(),
                    'workflowName' => $workflowItem->getDefinition()->getLabel(),
                    'stepName' => $workflowItem->getCurrentStep()->getLabel()
                ];
            }
        }

        $result = $this->repository->getGroupedWorkflowNameAndWorkflowStepName(
            'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            array_map(function (WorkflowAwareEntity $entity) {
                return $entity->getId();
            }, $entities)
        );
        /** @var WorkflowAwareEntity $entity */
        foreach ($entities as $entity) {
            $this->assertContains($entity->getId(), array_keys($result));
            foreach ($result[$entity->getId()] as $data) {
                $this->assertContains($data, $expectedData[$entity->getId()]);
            }
        }

    }

    public function testGetEntityIdsByEntityClassAndWorkflowStepIds()
    {
        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getReference('workflow_item.1');

        $result = $this->repository->getEntityIdsByEntityClassAndWorkflowStepIds(
            'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            [$workflowItem->getCurrentStep()->getId()]
        );

        $this->assertCount(LoadWorkflowAwareEntities::COUNT, $result);

        for ($i = 1; $i <= LoadWorkflowAwareEntities::COUNT; $i++) {
            $entity = $this->getReference('workflow_aware_entity.' . $i);

            $this->assertTrue(in_array((string)$entity->getId(), $result, true));
        }
    }

    public function testGetEntityIdsByEntityClassAndWorkflowNames()
    {
        /** @var WorkflowItem $workflowItem */
        $workflowItem = $this->getReference('workflow_item.1');

        $result = $this->repository->getEntityIdsByEntityClassAndWorkflowNames(
            'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            [$workflowItem->getWorkflowName()]
        );

        $this->assertCount(LoadWorkflowAwareEntities::COUNT, $result);

        for ($i = 1; $i <= LoadWorkflowAwareEntities::COUNT; $i++) {
            $entity = $this->getReference('workflow_aware_entity.' . $i);

            $this->assertTrue(in_array((string)$entity->getId(), $result, true));
        }
    }

    /**
     * @return array
     */
    protected function getEntityIdsByWorkflow()
    {
        $registry = $this->getContainer()->get('doctrine');
        $registry->getManager()->clear();

        /** @var EntityRepository $repository */
        $repository = $registry->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity');

        $qb = $repository->createQueryBuilder('e');
        $qb->select('e.id AS entityId', 'wi.workflowName AS workflowName')
            ->leftJoin(
                'OroWorkflowBundle:WorkflowItem',
                'wi',
                Join::WITH,
                'CAST(e.id as text) = wi.entityId AND wi.entityClass = ?1'
            )
            ->setParameter(1, 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity', \PDO::PARAM_STR)
            ->orderBy('e.id', 'ASC');

        $entities = $qb->getQuery()->getResult();

        $ids = ['none' => []];
        foreach ($entities as $entity) {
            $workflowName = $entity['workflowName'] ? : 'none';

            $ids[$workflowName][] = $entity['entityId'];
        }

        return $ids;
    }

    /**
     * @param array $allEntityIds
     * @param int $noneEntitiesCount
     * @param int $noStartStepEntitiesCount
     * @param int $withStartStepEntitiesCount
     * @param array $noneEntityIds
     * @param array $noStartStepEntityIds
     * @param array $withStartStepEntityIds
     */
    protected function assertEntityIdsByWorkflow(
        array $allEntityIds,
        $noneEntitiesCount,
        $noStartStepEntitiesCount,
        $withStartStepEntitiesCount,
        array $noneEntityIds = null,
        array $noStartStepEntityIds = null,
        array $withStartStepEntityIds = null
    )
    {
        if ($noneEntitiesCount > 0) {
            $actualAllEntities = $allEntityIds['none'];
            $this->assertCount($noneEntitiesCount, $actualAllEntities);
            if ($noneEntityIds !== null) {
                $this->assertEquals($noneEntityIds, $actualAllEntities);
            }
        } else {
            $this->assertEmpty($allEntityIds['none']);
        }

        if ($noStartStepEntitiesCount > 0) {
            $this->assertArrayHasKey(LoadWorkflowDefinitions::NO_START_STEP, $allEntityIds);
            $actualNoStartStepEntityIds = $allEntityIds[LoadWorkflowDefinitions::NO_START_STEP];
            $this->assertCount($noStartStepEntitiesCount, $actualNoStartStepEntityIds);
            if ($noStartStepEntityIds !== null) {
                $this->assertEquals($noStartStepEntityIds, $actualNoStartStepEntityIds);
            }
        } else {
            $this->assertArrayNotHasKey(LoadWorkflowDefinitions::NO_START_STEP, $allEntityIds);
        }

        if ($withStartStepEntitiesCount > 0) {
            $this->assertArrayHasKey(LoadWorkflowDefinitions::WITH_START_STEP, $allEntityIds);
            $actualWithStartStepEntityIds = $allEntityIds[LoadWorkflowDefinitions::WITH_START_STEP];
            $this->assertCount($withStartStepEntitiesCount, $actualWithStartStepEntityIds);
            if ($withStartStepEntityIds !== null) {
                $this->assertEquals($withStartStepEntityIds, $actualWithStartStepEntityIds);
            }
        } else {
            $this->assertArrayNotHasKey(LoadWorkflowDefinitions::WITH_START_STEP, $allEntityIds);
        }
    }

    /**
     * @param $workflowDefinitionName
     * @return null|WorkflowDefinition
     */
    protected function getWorkflowDefinitionByName($workflowDefinitionName)
    {
        $registry = $this->getContainer()->get('doctrine');
        $registry->getManager()->clear();

        /** @var EntityRepository $repository */
        $repository = $registry->getRepository('OroWorkflowBundle:WorkflowDefinition');

        return $repository->findOneBy(['name' => $workflowDefinitionName]);
    }

    public function testGetEntityWorkflowStepUpgradeQueryBuilder()
    {
        $definition = $this->getWorkflowDefinitionByName(LoadWorkflowDefinitions::WITH_START_STEP);
        $this->assertInstanceOf(
            'Doctrine\ORM\QueryBuilder',
            $this->repository->getEntityWorkflowStepUpgradeQueryBuilder($definition)
        );
    }
}
