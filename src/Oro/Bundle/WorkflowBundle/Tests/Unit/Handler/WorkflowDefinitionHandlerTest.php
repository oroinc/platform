<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowDefinitionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository */
    protected $entityRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    protected $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var StepManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $stepManager;

    /** @var WorkflowDefinitionHandler */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->stepManager = $this->getMockBuilder(StepManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->entityRepository);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry $managerRegistry */
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->handler = new WorkflowDefinitionHandler($this->eventDispatcher, $managerRegistry);
    }

    public function testCreateWorkflowDefinition()
    {
        $newDefinition = new WorkflowDefinition();

        $this->entityManager->expects($this->once())->method('persist')->with($newDefinition);
        $this->entityManager->expects($this->once())->method('flush');

        $changes = new WorkflowChangesEvent($newDefinition);

        $beforeEvent = WorkflowEvents::WORKFLOW_BEFORE_CREATE;
        $afterEvent = WorkflowEvents::WORKFLOW_AFTER_CREATE;

        $this->eventDispatcher->expects($this->at(0))->method('dispatch')->with($beforeEvent, $changes);
        $this->eventDispatcher->expects($this->at(1))->method('dispatch')->with($afterEvent, $changes);

        $this->handler->createWorkflowDefinition($newDefinition);
    }

    public function testUpdateWorkflowDefinition()
    {
        $existingDefinition = (new WorkflowDefinition())->setName('existing');
        $newDefinition = (new WorkflowDefinition())->setName('updated');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $changes = new WorkflowChangesEvent($existingDefinition, (new WorkflowDefinition())->setName('existing'));

        $beforeEvent = WorkflowEvents::WORKFLOW_BEFORE_UPDATE;
        $afterEvent = WorkflowEvents::WORKFLOW_AFTER_UPDATE;

        $this->eventDispatcher->expects($this->at(0))->method('dispatch')->with($beforeEvent, $changes);
        $this->eventDispatcher->expects($this->at(1))->method('dispatch')->with($afterEvent, $changes);

        $this->handler->updateWorkflowDefinition($existingDefinition, $newDefinition);
    }

    /**
     * @dataProvider deleteWorkflowDefinitionDataProvider
     *
     * @param WorkflowDefinition $definition
     * @param bool $expected
     */
    public function testDeleteWorkflowDefinition(WorkflowDefinition $definition, $expected)
    {
        $this->entityManager
            ->expects($this->exactly((int)$expected))
            ->method('remove');

        $this->entityManager
            ->expects($this->exactly((int)$expected))
            ->method('flush');

        $this->eventDispatcher
            ->expects($this->exactly((int)$expected))
            ->method('dispatch')
            ->with(WorkflowEvents::WORKFLOW_AFTER_DELETE, $this->equalTo(new WorkflowChangesEvent($definition)));

        $this->assertEquals($expected, $this->handler->deleteWorkflowDefinition($definition));
    }

    /**
     * @return array
     */
    public function deleteWorkflowDefinitionDataProvider()
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
