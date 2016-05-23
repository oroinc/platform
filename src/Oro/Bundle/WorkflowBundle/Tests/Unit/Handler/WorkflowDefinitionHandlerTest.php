<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;

class WorkflowDefinitionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository */
    protected $entityRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $entityManager;

    /** @var WorkflowDefinitionHandler */
    protected $handler;

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

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->handler = new WorkflowDefinitionHandler(
            $assembler,
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
     */
    public function testUpdateWorkflowDefinition(
        WorkflowDefinition $definition,
        WorkflowDefinition $existingDefinition = null,
        WorkflowDefinition $newDefinition = null
    ) {
        $this->assertNotEquals($definition, $newDefinition);

        if ($existingDefinition) {
            $this->assertNotEquals($definition, $existingDefinition);
            $this->entityRepository
                ->expects($this->once())
                ->method('find')
                ->willReturn($existingDefinition);
        }

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

        return [
            'with new definition' => [
                'definition' => $definition1,
                'existingDefinition' => null,
                'newDefinition' => $definition2,
            ],
            'with existing definition' => [
                'definition' => $definition3,
                'existingDefinition' => $definition1,
                'newDefinition' => null,
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
            ->expects($this->exactly((int) $expected))
            ->method('remove');

        $this->entityManager
            ->expects($this->exactly((int) $expected))
            ->method('flush');

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
