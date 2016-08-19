<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithGroups;

/**
 * @dbIsolation
 */
class WorkflowActivationTest extends WebTestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithGroups'
            ]
        );
    }

    public function testStartTransitionFormActionExclusiveGroups()
    {
        $workflowManager = $this->getContainer()->get('oro_workflow.manager');
        $workflowManager->activateWorkflow(LoadWorkflowDefinitionsWithGroups::WITH_GROUPS1);
        $workflowManager->activateWorkflow(LoadWorkflowDefinitionsWithGroups::WITH_GROUPS2);

        $entity = $this->createNewEntity();

        $this->assertNotEmpty($this->getWorkflowItem($entity, LoadWorkflowDefinitionsWithGroups::WITH_GROUPS1));
        $this->assertEmpty($this->getWorkflowItem($entity, LoadWorkflowDefinitionsWithGroups::WITH_GROUPS2));
    }

    /**
     * @return WorkflowAwareEntity
     */
    protected function createNewEntity()
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid('test', true));

        $entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(self::ENTITY_CLASS);
        $entityManager->persist($testEntity);
        $entityManager->flush($testEntity);

        return $testEntity;
    }

    /**
     * @param WorkflowAwareEntity $entity
     * @param $workflowName
     *
     * @return null|WorkflowItem
     */
    protected function getWorkflowItem(WorkflowAwareEntity $entity, $workflowName)
    {
        return $this->getContainer()->get('oro_workflow.manager')->getWorkflowItem($entity, $workflowName);
    }
}
