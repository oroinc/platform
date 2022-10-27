<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithGroups;

class WorkflowActivationTest extends WebTestCase
{
    /** @var WorkflowManager */
    private $workflowManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWorkflowDefinitionsWithGroups::class]);

        $this->workflowManager = $this->getContainer()->get('oro_workflow.manager');
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitionsWithGroups::WITH_GROUPS1);
        $this->workflowManager->activateWorkflow(LoadWorkflowDefinitionsWithGroups::WITH_GROUPS2);
    }

    public function testStartTransitionFormActionExclusiveGroups()
    {
        $entity = $this->createNewEntity();

        $this->assertNull($this->getWorkflowItem($entity, LoadWorkflowDefinitionsWithGroups::WITH_GROUPS1));
        $this->assertNotNull($this->getWorkflowItem($entity, LoadWorkflowDefinitionsWithGroups::WITH_GROUPS2));
    }

    private function createNewEntity(): WorkflowAwareEntity
    {
        $entity = new WorkflowAwareEntity();
        $entity->setName(uniqid('test_', true));

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $em->persist($entity);
        $em->flush($entity);
        $em->clear();

        return $entity;
    }

    private function getWorkflowItem(WorkflowAwareEntity $entity, string $workflowName): ?WorkflowItem
    {
        return $this->workflowManager->getWorkflowItem($entity, $workflowName);
    }
}
