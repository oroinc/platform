<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager;

class WorkflowRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $entityRepository;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $managerRegistry;

    /** @var WorkflowAssembler|\PHPUnit_Framework_MockObject_MockObject */
    private $assembler;

    /** @var WorkflowRegistry */
    private $registry;

    protected function setUp()
    {
        $this->entityRepository
            = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(WorkflowDefinition::class)
            ->willReturn($this->entityRepository);

        $this->managerRegistry->expects($this->any())
                ->method('getManagerForClass')
                ->with(WorkflowDefinition::class)
                ->willReturn($this->entityManager);

        /** @var WorkflowSystemConfigManager $workflowSystemConfig */
        $workflowSystemConfig = $this->getMockBuilder(WorkflowSystemConfigManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->assembler = $this->getMockBuilder(WorkflowAssembler::class)
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
            ->getMock();

        $this->registry = new WorkflowRegistry($this->managerRegistry, $this->assembler, $workflowSystemConfig);
    }

    protected function tearDown()
    {
        unset(
            $this->entityRepository,
            $this->managerRegistry,
            $this->entityManager,
            $this->assembler,
            $this->registry
        );
    }

    /**
     * @param WorkflowDefinition|null $workflowDefinition
     * @param Workflow|null $workflow
     */
    public function prepareAssemblerMock($workflowDefinition = null, $workflow = null)
    {
        if ($workflowDefinition && $workflow) {
            $this->assembler->expects($this->once())
                ->method('assemble')
                ->with($workflowDefinition)
                ->willReturn($workflow);
        } else {
            $this->assembler->expects($this->never())
                ->method('assemble');
        }
    }

    public function testGetWorkflow()
    {
        $workflowName = 'test_workflow';
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName);

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($workflowDefinition));

        $this->entityRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($workflowDefinition));
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition);

        // run twice to test cache storage inside registry
        $this->assertEquals($workflow, $this->registry->getWorkflow($workflowName));
        $this->assertEquals($workflow, $this->registry->getWorkflow($workflowName));
        $this->assertAttributeEquals(array($workflowName => $workflow), 'workflowByName', $this->registry);
    }

    public function testGetWorkflowWithDbEntitiesUpdate()
    {
        $workflowName = 'test_workflow';
        $oldDefinition = new WorkflowDefinition();
        $oldDefinition->setName($workflowName)->setLabel('Old Workflow');
        $newDefinition = new WorkflowDefinition();
        $newDefinition->setName($workflowName)->setLabel('New Workflow');

        /** @var Workflow $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $workflow->setDefinition($oldDefinition);

        $this->entityRepository->expects($this->at(0))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($oldDefinition));
        $this->entityRepository->expects($this->at(1))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($newDefinition));
        $this->prepareAssemblerMock($oldDefinition, $workflow);
        $this->setUpEntityManagerMock($oldDefinition, false);

        $this->assertEquals($workflow, $this->registry->getWorkflow($workflowName));
        $this->assertEquals($newDefinition, $workflow->getDefinition());
        $this->assertAttributeEquals(array($workflowName => $workflow), 'workflowByName', $this->registry);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "test_workflow" not found
     */
    public function testGetWorkflowNoUpdatedEntity()
    {
        $workflowName = 'test_workflow';
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName);

        /** @var Workflow $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $workflow->setDefinition($workflowDefinition);

        $this->entityRepository->expects($this->at(0))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($workflowDefinition));
        $this->entityRepository->expects($this->at(1))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue(null));
        $this->prepareAssemblerMock($workflowDefinition, $workflow);
        $this->setUpEntityManagerMock($workflowDefinition, false);

        $this->registry->getWorkflow($workflowName);
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param boolean $isEntityKnown
     */
    protected function setUpEntityManagerMock($workflowDefinition, $isEntityKnown = true)
    {
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->any())->method('isInIdentityMap')->with($workflowDefinition)
            ->will($this->returnValue($isEntityKnown));

        $this->entityManager->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "not_existing_workflow" not found
     */
    public function testGetWorkflowNotFoundException()
    {
        $workflowName = 'not_existing_workflow';

        $this->entityRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->willReturn(null);
        $this->prepareAssemblerMock();

        $this->registry->getWorkflow($workflowName);
    }
}
