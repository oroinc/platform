<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;

class ProcessConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $managerRegistry;

    /** @var ProcessDefinitionsConfigurator|\PHPUnit_Framework_MockObject_MockObject */
    protected $definitionsConfigurator;

    /** @var ProcessTriggersConfigurator|\PHPUnit_Framework_MockObject_MockObject */
    protected $triggersConfigurator;

    /** @var ProcessConfigurator */
    protected $processConfigurator;

    protected function setUp()
    {
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->definitionsConfigurator = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionsConfigurator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->triggersConfigurator = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processConfigurator = new ProcessConfigurator(
            $this->managerRegistry,
            $this->definitionsConfigurator,
            $this->triggersConfigurator,
            self::CLASS_NAME
        );
    }

    public function testConfigureProcesses()
    {
        $processConfigurations = [
            ProcessConfigurationProvider::NODE_DEFINITIONS => ['...definitions config'],
            ProcessConfigurationProvider::NODE_TRIGGERS => ['...triggers config']
        ];

        $this->definitionsConfigurator->expects($this->once())
            ->method('configureDefinitions')
            ->with(['...definitions config']);

        $this->definitionsConfigurator->expects($this->once())
            ->method('flush');

        //definitions repository mock
        $definitionsRepositoryMock = $this->assertObjectManagerCalledForRepository(self::CLASS_NAME);
        $definitionsRepositoryMock->expects($this->once())->method('findAll')->willReturn(['...definitions here']);

        $this->triggersConfigurator->expects($this->once())
            ->method('configureTriggers')
            ->with(['...triggers config']);

        $this->triggersConfigurator->expects($this->once())
            ->method('flush');

        $this->processConfigurator->configureProcesses($processConfigurations);
    }

    /**
     * @param string $entityClass
     * @return ObjectRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    public function assertObjectManagerCalledForRepository($entityClass)
    {
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repository);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($objectManager);

        return $repository;
    }
}
