<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowDefinitionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository */
    protected $entityRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $entityManager;

    /** @var WorkflowDefinitionHandler */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var WorkflowAssembler $assembler */
        $assembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->entityRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->handler = new WorkflowDefinitionHandler(
            $assembler,
            $this->eventDispatcher,
            $managerRegistry,
            'OroWorkflowBundle:WorkflowDefinition'
        );
    }

    /**
     * @dataProvider updateWorkflowDefinitionDataProvider
     *
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $existingDefinition
     * @param WorkflowDefinition $newDefinition
     * @param WorkflowDefinition $previous
     */
    public function testUpdateWorkflowDefinition(
        WorkflowDefinition $definition,
        WorkflowDefinition $existingDefinition = null,
        WorkflowDefinition $newDefinition = null,
        WorkflowDefinition $previous = null
    ) {
        $this->assertNotEquals($definition, $newDefinition);

        if ($existingDefinition) {
            $this->assertNotEquals($definition, $existingDefinition);
            $this->entityRepository
                ->expects($this->once())
                ->method('find')
                ->willReturn($existingDefinition);
        }

        if (!$existingDefinition && !$newDefinition) {
            $this->entityManager->expects($this->once())->method('persist')->with($definition);
        }

        $changes = new WorkflowChangesEvent($definition, $previous);

        $beforeEvent = $previous ? WorkflowEvents::WORKFLOW_BEFORE_UPDATE : WorkflowEvents::WORKFLOW_BEFORE_CREATE;
        $afterEvent = $previous ? WorkflowEvents::WORKFLOW_AFTER_UPDATE : WorkflowEvents::WORKFLOW_AFTER_CREATE;

        $this->eventDispatcher->expects($this->at(0))->method('dispatch')->with($beforeEvent, $changes);
        $this->eventDispatcher->expects($this->at(1))->method('dispatch')->with($afterEvent, $changes);

        $this->handler->updateWorkflowDefinition($definition, $newDefinition);

        if ($newDefinition) {
            $this->assertEquals($definition, $newDefinition);
        }

        if ($existingDefinition) {
            $this->assertEquals($definition, $existingDefinition);
        }
    }

    /**
     * @return array
     */
    public function updateWorkflowDefinitionDataProvider()
    {
        $definition1 = new WorkflowDefinition();
        $definition1
            ->setName('definition1')
            ->setLabel('label1');

        $definition2 = new WorkflowDefinition();
        $definition2
            ->setName('definition2')
            ->setLabel('label2');

        $definition3 = new WorkflowDefinition();
        $definition3
            ->setName('definition3')
            ->setLabel('label3');

        $definition4 = new WorkflowDefinition();
        $definition4
            ->setName('definition4')
            ->setLabel('label4');

        $definition5 = new WorkflowDefinition();

        return [
            'with new definition' => [
                'definition' => $definition1,
                'existingDefinition' => null,
                'newDefinition' => $definition2,
                'previous' => (new WorkflowDefinition())->import($definition1)
            ],
            'with existing definition' => [
                'definition' => $definition3,
                'existingDefinition' => $definition4,
                'newDefinition' => null,
                'previous' => (new WorkflowDefinition())->import($definition4)
            ],
            'created definition' => [
                'definition' => $definition1,
                'existingDefinition' => null,
                'newDefinition' => null,
                'previous' => null
            ],
            'with new definition without name' => [
                'definition' => $definition5,
                'existingDefinition' => null,
                'newDefinition' => $definition2,
                'previous' => (new WorkflowDefinition())->import($definition5)
            ],
        ];
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
