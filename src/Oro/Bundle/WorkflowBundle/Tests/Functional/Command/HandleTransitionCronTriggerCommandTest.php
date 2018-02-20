<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Command\HandleTransitionCronTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadTransitionTriggers;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

class HandleTransitionCronTriggerCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadTransitionTriggers::class]);
    }

    public function testExecute()
    {
        $entity = $this->createWorkflowAwareEntity();
        $workflowItem = $this->getWorkflowItem($entity->getId());

        $this->assertNotNull($workflowItem);
        $this->assertEquals('starting_point', $workflowItem->getCurrentStep()->getName());

        /** @var TransitionCronTrigger $trigger */
        $trigger = $this->getReference(LoadTransitionTriggers::TRIGGER_CRON);
        $this->assertNotNull($trigger);

        $result = $this->runCommand(HandleTransitionCronTriggerCommand::NAME, ['--id' => (string)$trigger->getId()]);

        $this->assertNotEmpty($result);
        $this->assertContains(
            sprintf(
                'Transition cron trigger #%d of workflow "%s" successfully finished',
                $trigger->getId(),
                $trigger->getWorkflowName()
            ),
            $result
        );

        $workflowItem = $this->getWorkflowItem($entity->getId());

        $this->assertNotNull($workflowItem);
        $this->assertEquals('third_point', $workflowItem->getCurrentStep()->getName());
    }

    /**
     * @return WorkflowAwareEntity
     */
    protected function createWorkflowAwareEntity()
    {
        $manager = $this->getObjectManager(WorkflowAwareEntity::class);

        $obj = new WorkflowAwareEntity();
        $obj->setName('test');

        $manager->persist($obj);
        $manager->flush();

        return $obj;
    }

    /**
     * @param int $entityId
     * @return WorkflowItem
     */
    protected function getWorkflowItem($entityId)
    {
        /** @var WorkflowItemRepository $repository */
        $repository = $this->getRepository(WorkflowItem::class);

        return $repository->findOneByEntityMetadata(
            WorkflowAwareEntity::class,
            $entityId,
            LoadWorkflowDefinitions::WITH_GROUPS1
        );
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    protected function getObjectManager($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getObjectManager($className)->getRepository($className);
    }
}
