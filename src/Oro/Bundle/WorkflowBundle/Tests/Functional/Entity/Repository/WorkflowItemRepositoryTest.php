<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\FunctionalTestCase;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntities;

/**
 * @db_isolation
 * @db_reindex
 */
class WorkflowItemRepositoryTest extends FunctionalTestCase
{
    /**
     * @var WorkflowItemRepository
     */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroWorkflowBundle:WorkflowItem');
    }

    public function testResetWorkflowData()
    {
        // load two workflow definitions and create 20 entities with workflow items for each
        $this->loadFixtures(
            array(
                'Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions',
                'Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowAwareEntities',
            )
        );

        // assert input state
        $inputEntityIds = $this->getEntityIdsByWorkflow();
        $this->assertEmpty($inputEntityIds['none']);
        $this->assertArrayHasKey(LoadWorkflowDefinitions::NO_START_STEP, $inputEntityIds);
        $this->assertArrayHasKey(LoadWorkflowDefinitions::WITH_START_STEP, $inputEntityIds);
        $this->assertCount(LoadWorkflowAwareEntities::COUNT, $inputEntityIds[LoadWorkflowDefinitions::NO_START_STEP]);
        $this->assertCount(LoadWorkflowAwareEntities::COUNT, $inputEntityIds[LoadWorkflowDefinitions::WITH_START_STEP]);

        // reset only second workflow data with more than one batch
        $this->repository->resetWorkflowData(
            'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity',
            array(LoadWorkflowDefinitions::NO_START_STEP),
            LoadWorkflowAwareEntities::COUNT - 1
        );

        $updatedEntityIds = $this->getEntityIdsByWorkflow();
        $this->assertArrayHasKey(LoadWorkflowDefinitions::NO_START_STEP, $inputEntityIds);
        $this->assertArrayNotHasKey(LoadWorkflowDefinitions::WITH_START_STEP, $updatedEntityIds);
        $this->assertCount(LoadWorkflowAwareEntities::COUNT, $updatedEntityIds['none']);
        $this->assertCount(LoadWorkflowAwareEntities::COUNT, $inputEntityIds[LoadWorkflowDefinitions::NO_START_STEP]);
        $this->assertEquals(
            $inputEntityIds[LoadWorkflowDefinitions::NO_START_STEP],
            $updatedEntityIds[LoadWorkflowDefinitions::NO_START_STEP]
        );
        $this->assertEquals($inputEntityIds[LoadWorkflowDefinitions::WITH_START_STEP], $updatedEntityIds['none']);
        $this->assertEntitiesHaveNoWorkflowData($updatedEntityIds['none']);

        // reset all workflow data
        $this->repository->resetWorkflowData('Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity');

        $emptyEntityIds = $this->getEntityIdsByWorkflow();
        $this->assertArrayNotHasKey(LoadWorkflowDefinitions::NO_START_STEP, $emptyEntityIds);
        $this->assertArrayNotHasKey(LoadWorkflowDefinitions::WITH_START_STEP, $emptyEntityIds);
        $this->assertCount(LoadWorkflowAwareEntities::COUNT * 2, $emptyEntityIds['none']);
        $this->assertEquals(
            array_merge(
                $inputEntityIds[LoadWorkflowDefinitions::NO_START_STEP],
                $inputEntityIds[LoadWorkflowDefinitions::WITH_START_STEP]
            ),
            $emptyEntityIds['none']
        );
        $this->assertEntitiesHaveNoWorkflowData($updatedEntityIds['none']);
    }

    /**
     * @return array
     */
    protected function getEntityIdsByWorkflow()
    {
        $registry = $this->getContainer()->get('doctrine');
        $registry->getManager()->clear();

        /** @var WorkflowAwareEntity[] $entities */
        $entities = $registry->getRepository('OroTestFrameworkBundle:WorkflowAwareEntity')->findAll();

        $ids = array('none' => array());
        foreach ($entities as $entity) {
            $workflowName = $entity->getWorkflowItem() ? $entity->getWorkflowItem()->getWorkflowName() : 'none';
            $ids[$workflowName][] = $entity->getId();
        }

        return $ids;
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
}
