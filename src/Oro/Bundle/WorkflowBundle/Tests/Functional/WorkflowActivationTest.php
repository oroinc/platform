<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithGroups;

class WorkflowActivationTest extends WebTestCase
{
    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var EntityManager */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWorkflowDefinitionsWithGroups::class]);

        $this->workflowManager = $this->getContainer()->get('oro_workflow.manager');
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitionsWithGroups::WITH_GROUPS1);
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitionsWithGroups::WITH_GROUPS2);

        $this->entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
    }

    public function testStartTransitionFormActionExclusiveGroups()
    {
        $entity = $this->createNewEntity();

        $this->assertNull($this->getWorkflowItem($entity, LoadWorkflowDefinitionsWithGroups::WITH_GROUPS1));
        $this->assertNotNull($this->getWorkflowItem($entity, LoadWorkflowDefinitionsWithGroups::WITH_GROUPS2));
    }

    /**
     * @return WorkflowAwareEntity
     */
    protected function createNewEntity()
    {
        $entity = new WorkflowAwareEntity();
        $entity->setName(uniqid('test_', true));

        $this->entityManager->persist($entity);
        $this->entityManager->flush($entity);
        $this->entityManager->clear();

        return $entity;
    }

    /**
     * @param WorkflowAwareEntity $entity
     * @param string $workflowName
     *
     * @return null|WorkflowItem
     */
    protected function getWorkflowItem(WorkflowAwareEntity $entity, $workflowName)
    {
        return $this->workflowManager->getWorkflowItem($entity, $workflowName);
    }
}
