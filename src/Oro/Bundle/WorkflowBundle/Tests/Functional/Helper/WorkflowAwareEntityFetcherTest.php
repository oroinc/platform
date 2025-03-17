<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

class WorkflowAwareEntityFetcherTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWorkflowDefinitions::class]);
    }

    public function testGetEntitiesWithoutWorkflowItem(): void
    {
        $workflowManager = $this->getContainer()->get('oro_workflow.manager');

        $entity1 = $this->createWorkflowAwareEntity('test1');
        $entity2 = $this->createWorkflowAwareEntity('test2');

        self::assertNull($workflowManager->getWorkflowItem($entity1, LoadWorkflowDefinitions::WITH_GROUPS1));
        self::assertNull($workflowManager->getWorkflowItem($entity2, LoadWorkflowDefinitions::WITH_GROUPS1));

        $workflow = $workflowManager->getWorkflow(LoadWorkflowDefinitions::WITH_GROUPS1);
        self::assertNotNull($workflow);

        $helper = $this->getContainer()->get('oro_workflow.helper.workflow_aware_entity_fetcher');

        $result = $helper->getEntitiesWithoutWorkflowItem($workflow->getDefinition());

        self::assertCount(2, $result);
        self::assertContains($entity1, $result);
        self::assertContains($entity2, $result);

        self::assertEquals(
            [$entity1],
            $helper->getEntitiesWithoutWorkflowItem($workflow->getDefinition(), "e.name = 'test1'")
        );

        self::assertEquals(
            [],
            $helper->getEntitiesWithoutWorkflowItem($workflow->getDefinition(), "e.name = 'test'")
        );
    }

    private function createWorkflowAwareEntity(string $name): WorkflowAwareEntity
    {
        $obj = new WorkflowAwareEntity();
        $obj->setName($name);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $em->persist($obj);
        $em->flush();

        return $obj;
    }
}
