<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowDefinitionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var WorkflowDefinitionHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->handler = new WorkflowDefinitionHandler($this->eventDispatcher, $doctrine);
    }

    public function testCreateWorkflowDefinition()
    {
        $newDefinition = new WorkflowDefinition();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($newDefinition);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $changes = new WorkflowChangesEvent($newDefinition);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$changes, WorkflowEvents::WORKFLOW_BEFORE_CREATE],
                [$changes, WorkflowEvents::WORKFLOW_AFTER_CREATE]
            );

        $this->handler->createWorkflowDefinition($newDefinition);
    }

    public function testUpdateWorkflowDefinition()
    {
        $existingDefinition = (new WorkflowDefinition())->setName('existing');
        $newDefinition = (new WorkflowDefinition())->setName('updated');

        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        $changes = new WorkflowChangesEvent($existingDefinition, (new WorkflowDefinition())->setName('existing'));

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$changes, WorkflowEvents::WORKFLOW_BEFORE_UPDATE],
                [$changes, WorkflowEvents::WORKFLOW_AFTER_UPDATE]
            );

        $this->handler->updateWorkflowDefinition($existingDefinition, $newDefinition);
    }

    /**
     * @dataProvider deleteWorkflowDefinitionDataProvider
     */
    public function testDeleteWorkflowDefinition(WorkflowDefinition $definition, bool $expected)
    {
        $this->entityManager->expects($this->exactly((int)$expected))
            ->method('remove');
        $this->entityManager->expects($this->exactly((int)$expected))
            ->method('flush');

        $this->eventDispatcher->expects($this->exactly((int)$expected))
            ->method('dispatch')
            ->with(new WorkflowChangesEvent($definition), WorkflowEvents::WORKFLOW_AFTER_DELETE);

        $this->assertEquals($expected, $this->handler->deleteWorkflowDefinition($definition));
    }

    public function deleteWorkflowDefinitionDataProvider(): array
    {
        $definition1 = new WorkflowDefinition();
        $definition1
            ->setName('definition1')
            ->setLabel('label1');

        $definition2 = new WorkflowDefinition();
        $definition2
            ->setName('definition2')
            ->setLabel('label2')
            ->setSystem(true);

        return [
            'with new definition' => [
                'definition' => $definition1,
                'expected' => true,
            ],
            'with existing definition' => [
                'definition' => $definition2,
                'expected' => false,
            ],
        ];
    }
}
