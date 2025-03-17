<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowDefinitionHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private WorkflowDefinitionHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->handler = new WorkflowDefinitionHandler($this->eventDispatcher, $doctrine);
    }

    public function testCreateWorkflowDefinition(): void
    {
        $newDefinition = new WorkflowDefinition();

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with($newDefinition);
        $this->entityManager->expects(self::once())
            ->method('flush');

        $changes = new WorkflowChangesEvent($newDefinition);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$changes, WorkflowEvents::WORKFLOW_BEFORE_CREATE],
                [$changes, WorkflowEvents::WORKFLOW_AFTER_CREATE]
            );

        $this->handler->createWorkflowDefinition($newDefinition);
    }

    public function testUpdateWorkflowDefinition(): void
    {
        $existingDefinition = (new WorkflowDefinition())->setName('existing');
        $newDefinition = (new WorkflowDefinition())->setName('updated');

        $this->entityManager->expects(self::once())
            ->method('persist');
        $this->entityManager->expects(self::once())
            ->method('flush');

        $changes = new WorkflowChangesEvent($existingDefinition, (new WorkflowDefinition())->setName('existing'));

        $this->eventDispatcher->expects(self::exactly(2))
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
    public function testDeleteWorkflowDefinition(WorkflowDefinition $definition, bool $expected): void
    {
        $this->entityManager->expects(self::exactly((int)$expected))
            ->method('remove');
        $this->entityManager->expects(self::exactly((int)$expected))
            ->method('flush');

        $this->eventDispatcher->expects(self::exactly((int)$expected))
            ->method('dispatch')
            ->with(new WorkflowChangesEvent($definition), WorkflowEvents::WORKFLOW_AFTER_DELETE);

        self::assertEquals($expected, $this->handler->deleteWorkflowDefinition($definition));
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
