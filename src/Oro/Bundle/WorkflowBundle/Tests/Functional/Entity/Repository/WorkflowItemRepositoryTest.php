<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
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

        $this->assertEquals([$item], $actual);
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

    public function testResetWorkflowData()
    {
        // assert input state
        // - no entities without workflow items
        // - 20 entities with NO_START_STEP workflow item
        // - 20 entities with WITH_START_STEP workflow item
        $inputEntityIds = $this->getEntityIdsByWorkflow();
        $this->assertEntityIdsByWorkflow(
            $inputEntityIds,
            0,
            LoadWorkflowAwareEntities::COUNT,
            LoadWorkflowAwareEntities::COUNT
        );
        $noStartStepEntityIds = $inputEntityIds[LoadWorkflowDefinitions::NO_START_STEP];
        $withStartStepEntityIds = $inputEntityIds[LoadWorkflowDefinitions::WITH_START_STEP];

        // reset only WITH_START_STEP workflow data with more than one batch
        $this->repository->resetWorkflowData(
            'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            [LoadWorkflowDefinitions::NO_START_STEP],
            LoadWorkflowAwareEntities::COUNT - 1
        );

        // assert state: NO_START_STEP workflow entities weren't changed, WITH_START_STEP workflow entities were reset
        // - 20 entities without workflow items
        // - 20 entities with NO_START_STEP workflow item
        // - no entities with WITH_START_STEP workflow item
        $updatedEntityIds = $this->getEntityIdsByWorkflow();
        $this->assertEntityIdsByWorkflow(
            $updatedEntityIds,
            LoadWorkflowAwareEntities::COUNT,
            LoadWorkflowAwareEntities::COUNT,
            0,
            $withStartStepEntityIds,
            $noStartStepEntityIds,
            []
        );

        // reset all workflow data
        $this->repository->resetWorkflowData('Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity');

        // assert state: both NO_START_STEP and WITH_START_STEP workflow entities were reset
        // - 40 entities without workflow items
        // - no entities with NO_START_STEP workflow item
        // - no entities with WITH_START_STEP workflow item
        $emptyEntityIds = $this->getEntityIdsByWorkflow();
        $this->assertEntityIdsByWorkflow(
            $emptyEntityIds,
            LoadWorkflowAwareEntities::COUNT * 2,
            0,
            0,
            array_merge($noStartStepEntityIds, $withStartStepEntityIds),
            [],
            []
        );
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
                'wi.entityId = e.id AND wi.entityClass = ?1'
            )
            ->setParameter(1, 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity', \PDO::PARAM_STR)
            ->orderBy('e.id', 'ASC');

        $entities = $qb->getQuery()->getResult();

        $ids = ['none' => []];
        foreach ($entities as $entity) {
            $workflowName = $entity['workflowName'] ?: 'none';

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
    ) {
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
}
