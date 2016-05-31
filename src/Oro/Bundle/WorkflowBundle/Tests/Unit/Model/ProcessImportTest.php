<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsConfigurator;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersConfigurator;
use Oro\Bundle\WorkflowBundle\Model\ProcessConfigurator;

class ProcessImportTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition';

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var ProcessDefinitionsConfigurator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $definitionImport;

    /**
     * @var ProcessTriggersConfigurator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggersImport;

    /**
     * @var ProcessConfigurator
     */
    protected $processImport;

    protected function setUp()
    {
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->definitionImport = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsConfigurator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->triggersImport = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersImport')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processImport = new ProcessConfigurator(
            $this->managerRegistry,
            $this->definitionImport,
            $this->triggersImport,
            self::CLASS_NAME
        );
    }

    public function testImport()
    {
        $processConfigurations = [
            ProcessConfigurationProvider::NODE_DEFINITIONS => ['...definitions config'],
            ProcessConfigurationProvider::NODE_TRIGGERS => ['...triggers config']
        ];

        $definitionsImported = [new ProcessDefinition()];
        $triggersImported = [new ProcessTrigger()];
        $createdSchedules = [new Schedule()];

        $this->definitionImport->expects($this->once())
            ->method('import')
            ->with(['...definitions config'])
            ->willReturn($definitionsImported);

        //definitions repository mock
        $definitionsRepositoryMock = $this->assertObjectManagerCalledForRepository(self::CLASS_NAME);
        $definitionsRepositoryMock->expects($this->once())->method('findAll')->willReturn(['...definitions here']);

        $this->triggersImport->expects($this->once())->method('import')->with(
            ['...triggers config'],
            ['...definitions here']
        )->willReturn($triggersImported);

        $this->triggersImport->expects($this->once())->method('getCreatedSchedules')->willReturn($createdSchedules);

        $result = $this->processImport->import($processConfigurations);

        $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Model\Import\ProcessImportResult', $result);

        $this->assertEquals($definitionsImported, $result->getDefinitions());
        $this->assertEquals($triggersImported, $result->getTriggers());
        $this->assertEquals($createdSchedules, $result->getSchedules());
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
