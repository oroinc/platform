<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Psr\Log\AbstractLogger;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

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

    public function testSetLogger()
    {
        $reflection = new \ReflectionClass('Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator');
        $property = $reflection->getProperty('logger');
        $property->setAccessible(true);
        /** @var AbstractLogger $logger */
        $logger = $this->getMockForAbstractClass('Psr\Log\AbstractLogger');
        $this->assertNotEquals($logger, $property->getValue($this->processConfigurator));
        $this->processConfigurator->setLogger($logger);
        $this->assertEquals($logger, $property->getValue($this->processConfigurator));
    }

    public function testRemoveProcesses()
    {
        $definition = new ProcessDefinition();
        $names = [
            'process_exist',
            'process_not_exist',
        ];

        //definitions repository mock
        $definitionsRepositoryMock = $this->assertObjectManagerCalledForRepository(self::CLASS_NAME);
        $definitionsRepositoryMock->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                ['process_exist', $definition],
                ['process_not_exist', null],
            ]);

        $this->definitionsConfigurator->expects($this->once())
            ->method('removeDefinition')
            ->with('process_exist');

        $this->definitionsConfigurator->expects($this->once())
            ->method('flush');

        $this->triggersConfigurator->expects($this->once())
            ->method('removeDefinitionTriggers')
            ->with($definition);

        $this->triggersConfigurator->expects($this->once())
            ->method('flush');

        $this->processConfigurator->removeProcesses($names);
    }

    /**
     * @param string $entityClass
     * @return ObjectRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private function assertObjectManagerCalledForRepository($entityClass)
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
