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
    CONST WORKFLOW_AWARE_ENTITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';

    /**
     * @var WorkflowItemRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(array('Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntities'));

        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroWorkflowBundle:WorkflowItem');
    }

    public function testFindAllByEntityMetadata()
    {

        $entityIds = $this->getEntityIdsByWorkflow();
        $entityId = reset($entityIds[LoadWorkflowDefinitions::NO_START_STEP]);
        $this->assertInternalType('array', $this->repository->findAllByEntityMetadata(self::WORKFLOW_AWARE_ENTITY_CLASS, $entityId));
    }

    public function testFindOneByEntityMetadata()
    {
        $entityIds = $this->getEntityIdsByWorkflow();
        $entityId = reset($entityIds[LoadWorkflowDefinitions::NO_START_STEP]);

        $this->assertNull($this->repository->findOneByEntityMetadata(self::WORKFLOW_AWARE_ENTITY_CLASS, $entityId, 'SOME_NON_EXISTING_WORKFLOW'));

        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
            $this->repository->findOneByEntityMetadata(self::WORKFLOW_AWARE_ENTITY_CLASS, $entityId, LoadWorkflowDefinitions::NO_START_STEP)
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
            array(LoadWorkflowDefinitions::NO_START_STEP),
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
            array()
        );
        $this->assertEntitiesHaveNoWorkflowData($updatedEntityIds['none']);

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
            array(),
            array()
        );
        $this->assertEntitiesHaveNoWorkflowData($emptyEntityIds['none']);
    }


    /**
     * @param array $entityIds
     */
    protected function assertEntitiesHaveNoWorkflowData(array $entityIds)
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity');

        $queryBuilder = $repository->createQueryBuilder('entity');
        $queryBuilder->andWhere($queryBuilder->expr()->in('entity.id', $entityIds));

        /** @var WorkflowAwareEntity[] $entities */
        $entities = $queryBuilder->getQuery()->execute();
        foreach ($entities as $entity) {
            $this->assertNull($entity->getWorkflowItem());
            $this->assertNull($entity->getWorkflowStep());
        }
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

    /**
     * @return array
     */
    protected function getEntityIdsByWorkflow()
    {
        $registry = $this->getContainer()->get('doctrine');
        $registry->getManager()->clear();

        /** @var WorkflowAwareEntity[] $entities */
        $entities = $registry
            ->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity')
            ->findBy(
                array(),
                array('id' => 'ASC')
            );

        $ids = array('none' => array());
        foreach ($entities as $entity) {
            $workflowName = $entity->getWorkflowItem() ? $entity->getWorkflowItem()->getWorkflowName() : 'none';
            $ids[$workflowName][] = $entity->getId();
        }

        return $ids;
    }

}
