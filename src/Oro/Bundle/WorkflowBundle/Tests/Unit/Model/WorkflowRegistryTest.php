<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

class WorkflowRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createWorkflowDefinitionRepositoryMock()
    {
        $workflowDefinitionRepository
            = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('find', 'findByEntityClass'))
            ->getMock();

        return $workflowDefinitionRepository;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|null $workflowDefinitionRepository
     * @return ManagerRegistry
     */
    protected function createManagerRegistryMock($workflowDefinitionRepository = null)
    {
        $managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->setMethods(array('getRepository'))
            ->getMockForAbstractClass();
        if ($workflowDefinitionRepository) {
            $managerRegistry->expects($this->any())
                ->method('getRepository')
                ->with('OroWorkflowBundle:WorkflowDefinition')
                ->will($this->returnValue($workflowDefinitionRepository));
        }

        return $managerRegistry;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createConfigurationProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param WorkflowDefinition|null $workflowDefinition
     * @param Workflow|null $workflow
     * @return WorkflowAssembler
     */
    public function createWorkflowAssemblerMock($workflowDefinition = null, $workflow = null)
    {
        $workflowAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler')
            ->disableOriginalConstructor()
            ->setMethods(array('assemble'))
            ->getMock();
        if ($workflowDefinition && $workflow) {
            $workflowAssembler->expects($this->once())
                ->method('assemble')
                ->with($workflowDefinition)
                ->will($this->returnValue($workflow));
        } else {
            $workflowAssembler->expects($this->never())
                ->method('assemble');
        }

        return $workflowAssembler;
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

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($workflowDefinition));
        $managerRegistry = $this->createManagerRegistryMock($workflowDefinitionRepository);
        $workflowAssembler = $this->createWorkflowAssemblerMock($workflowDefinition, $workflow);
        $configProvider = $this->createConfigurationProviderMock();
        $this->setUpEntityManagerMock($managerRegistry, $workflowDefinition);

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        // run twice to test cache storage inside registry
        $this->assertEquals($workflow, $workflowRegistry->getWorkflow($workflowName));
        $this->assertEquals($workflow, $workflowRegistry->getWorkflow($workflowName));
        $this->assertAttributeEquals(array($workflowName => $workflow), 'workflowByName', $workflowRegistry);
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

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->at(0))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($oldDefinition));
        $workflowDefinitionRepository->expects($this->at(1))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($newDefinition));
        $managerRegistry = $this->createManagerRegistryMock($workflowDefinitionRepository);
        $workflowAssembler = $this->createWorkflowAssemblerMock($oldDefinition, $workflow);
        $configProvider = $this->createConfigurationProviderMock();
        $this->setUpEntityManagerMock($managerRegistry, $oldDefinition, false);

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $this->assertEquals($workflow, $workflowRegistry->getWorkflow($workflowName));
        $this->assertEquals($newDefinition, $workflow->getDefinition());
        $this->assertAttributeEquals(array($workflowName => $workflow), 'workflowByName', $workflowRegistry);
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

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->at(0))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($workflowDefinition));
        $workflowDefinitionRepository->expects($this->at(1))
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue(null));
        $managerRegistry = $this->createManagerRegistryMock($workflowDefinitionRepository);
        $workflowAssembler = $this->createWorkflowAssemblerMock($workflowDefinition, $workflow);
        $configProvider = $this->createConfigurationProviderMock();
        $this->setUpEntityManagerMock($managerRegistry, $workflowDefinition, false);

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $workflowRegistry->getWorkflow($workflowName);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $managerRegistry
     * @param WorkflowDefinition $workflowDefinition
     * @param boolean $isEntityKnown
     */
    protected function setUpEntityManagerMock($managerRegistry, $workflowDefinition, $isEntityKnown = true)
    {
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->any())->method('isInIdentityMap')->with($workflowDefinition)
            ->will($this->returnValue($isEntityKnown));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getUnitOfWork'])
            ->getMock();
        $entityManager->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));

        $managerRegistry->expects($this->any())->method('getManagerForClass')
            ->with('OroWorkflowBundle:WorkflowDefinition')->will($this->returnValue($entityManager));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "not_existing_workflow" not found
     */
    public function testGetWorkflowNotFoundException()
    {
        $workflowName = 'not_existing_workflow';

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue(null));
        $managerRegistry = $this->createManagerRegistryMock($workflowDefinitionRepository);
        $workflowAssembler = $this->createWorkflowAssemblerMock();
        $configProvider = $this->createConfigurationProviderMock();

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $workflowRegistry->getWorkflow($workflowName);
    }

    public function testGetActiveWorkflowByEntityClassWithoutWorkflowName()
    {
        $workflowName = 'test_workflow';
        $entityClass = '\stdClass';
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        list($managerRegistry, $workflowAssembler, $configProvider)
            = $this->prepareArgumentsForGetWorkflowForEntityClass($entityClass, $workflowName, $workflow);

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $this->assertEquals($workflow, $workflowRegistry->getActiveWorkflowByEntityClass($entityClass));
    }

    /**
     * @param string $entityClass
     * @param string|null $workflowName
     * @param \PHPUnit_Framework_MockObject_MockObject $workflow
     * @return array
     */
    protected function prepareArgumentsForGetWorkflowForEntityClass($entityClass, $workflowName, $workflow)
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName)
            ->setRelatedEntity($entityClass);

        $workflow->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($workflowDefinition));

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($workflowDefinition));

        $managerRegistry = $this->createManagerRegistryMock($workflowDefinitionRepository);

        $workflowAssembler = $this->createWorkflowAssemblerMock($workflowDefinition, $workflow);

        $configProvider = $this->createConfigurationProviderMock();
        $configProvider->expects($this->any())
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(true));

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->getMock();
        $config->expects($this->any())
            ->method('get')
            ->with('active_workflow')
            ->will($this->returnValue($workflowName));

        $configProvider->expects($this->any())
            ->method('getConfig')
            ->with($entityClass)
            ->will($this->returnValue($config));

        $this->setUpEntityManagerMock($managerRegistry, $workflowDefinition);

        return array($managerRegistry, $workflowAssembler, $configProvider);
    }

    public function testGetWorkflowByEntityClassNoEntityConfig()
    {
        $entityClass = '\stdClass';

        $managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $managerRegistry->expects($this->never())
            ->method($this->anything());

        $workflowAssembler = $this->createWorkflowAssemblerMock();
        $configProvider = $this->createConfigurationProviderMock();
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(false));

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $this->assertNull($workflowRegistry->getActiveWorkflowByEntityClass($entityClass));
    }

    public function testGetActiveWorkflowByEntityClassNoActiveWorkflow()
    {
        $entityClass = '\stdClass';

        $managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $managerRegistry->expects($this->never())
            ->method($this->anything());

        $workflowAssembler = $this->createWorkflowAssemblerMock();
        $configProvider = $this->createConfigurationProviderMock();
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(true));
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('active_workflow')
            ->will($this->returnValue(null));
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->will($this->returnValue($config));

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $this->assertNull($workflowRegistry->getActiveWorkflowByEntityClass($entityClass));
    }

    public function testGetActiveWorkflowByEntityClassNoWorkflow()
    {
        $entityClass = '\stdClass';
        $workflowName = 'test_workflow';
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName);

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue(null));
        $managerRegistry = $this->createManagerRegistryMock($workflowDefinitionRepository);
        $workflowAssembler = $this->createWorkflowAssemblerMock();
        $configProvider = $this->createConfigurationProviderMock();
        $configProvider->expects($this->any())
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(true));
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->getMock();
        $config->expects($this->any())
            ->method('get')
            ->with('active_workflow')
            ->will($this->returnValue($workflowName));
        $configProvider->expects($this->any())
            ->method('getConfig')
            ->with($entityClass)
            ->will($this->returnValue($config));

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $this->assertNull($workflowRegistry->getActiveWorkflowByEntityClass($entityClass));
    }

    public function testHasActiveWorkflowByEntityClass()
    {
        $entityClass = '\stdClass';
        $workflowName = 'test_workflow';
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        list($managerRegistry, $workflowAssembler, $configProvider)
            = $this->prepareArgumentsForGetWorkflowForEntityClass($entityClass, $workflowName, $workflow);

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $this->assertTrue($workflowRegistry->hasActiveWorkflowByEntityClass($entityClass));
    }

    public function testHasActiveWorkflowByEntityClassNoWorkflow()
    {
        $entityClass = '\stdClass';

        $managerRegistry = $this->createManagerRegistryMock();
        $workflowAssembler = $this->createWorkflowAssemblerMock();
        $configProvider = $this->createConfigurationProviderMock();
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->will($this->returnValue(false));

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $this->assertFalse($workflowRegistry->hasActiveWorkflowByEntityClass($entityClass));
    }
}
