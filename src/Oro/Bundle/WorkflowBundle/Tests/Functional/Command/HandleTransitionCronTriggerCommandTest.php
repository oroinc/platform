<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

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
    protected function setUp(): void
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

        $result = $this->runCommand(
            HandleTransitionCronTriggerCommand::getDefaultName(),
            ['--id' => (string)$trigger->getId()]
        );

        $this->assertNotEmpty($result);
        self::assertStringContainsString(
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

    private function createWorkflowAwareEntity(): WorkflowAwareEntity
    {
        $obj = new WorkflowAwareEntity();
        $obj->setName('test');

        $em = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $em->persist($obj);
        $em->flush();

        return $obj;
    }

    private function getWorkflowItem(int $entityId): WorkflowItem
    {
        /** @var WorkflowItemRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(WorkflowItem::class);

        return $repository->findOneByEntityMetadata(
            WorkflowAwareEntity::class,
            $entityId,
            LoadWorkflowDefinitions::WITH_GROUPS1
        );
    }
}
