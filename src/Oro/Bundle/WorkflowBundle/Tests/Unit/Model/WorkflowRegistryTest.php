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
            $managerRegistry->expects($this->once())
                ->method('getRepository')
                ->with('OroWorkflowBundle:WorkflowDefinition')
                ->will($this->returnValue($workflowDefinitionRepository));
        }

        return $managerRegistry;
    }

    protected function createConfigurationProviderMock()
    {
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
            ->getMock();
        return $provider;
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
        /** @var Workflow $workflow */
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->once())
            ->method('find')
            ->with($workflowName)
            ->will($this->returnValue($workflowDefinition));
        $managerRegistry = $this->createManagerRegistryMock($workflowDefinitionRepository);
        $workflowAssembler = $this->createWorkflowAssemblerMock($workflowDefinition, $workflow);
        $configProvider = $this->createConfigurationProviderMock();

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        // run twice to test cache storage inside registry
        $this->assertEquals($workflow, $workflowRegistry->getWorkflow($workflowName));
        $this->assertEquals($workflow, $workflowRegistry->getWorkflow($workflowName));
        $this->assertAttributeEquals(array($workflowName => $workflow), 'workflowByName', $workflowRegistry);
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

    public function testGetWorkflowByEntityClass()
    {
        $entityClass = '\stdClass';
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        list($managerRegistry, $workflowAssembler, $configProvider)
            = $this->prepareArgumentsForGetWorkflowForEntityClass($entityClass, $workflow);

        $workflowRegistry = new WorkflowRegistry($managerRegistry, $workflowAssembler, $configProvider);
        $this->assertEquals($workflow, $workflowRegistry->getActiveWorkflowByEntityClass($entityClass));
    }

    /**
     * @param string $entityClass
     * @param Workflow $workflow
     * @return array
     */
    protected function prepareArgumentsForGetWorkflowForEntityClass($entityClass, Workflow $workflow)
    {
        $workflowName = 'test_workflow';
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName);

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->once())
            ->method('findByEntityClass')
            ->with($entityClass, $workflowName)
            ->will($this->returnValue(array($workflowDefinition)));
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

    public function testGetWorkflowByEntityClassNoActiveWorkflow()
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

    public function testGetWorkflowByEntityClassNoWorkflow()
    {
        $entityClass = '\stdClass';
        $workflowName = 'test_workflow';
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($workflowName);

        $workflowDefinitionRepository = $this->createWorkflowDefinitionRepositoryMock();
        $workflowDefinitionRepository->expects($this->once())
            ->method('findByEntityClass')
            ->with($entityClass, $workflowName)
            ->will($this->returnValue(array()));
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
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        list($managerRegistry, $workflowAssembler, $configProvider)
            = $this->prepareArgumentsForGetWorkflowForEntityClass($entityClass, $workflow);

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
