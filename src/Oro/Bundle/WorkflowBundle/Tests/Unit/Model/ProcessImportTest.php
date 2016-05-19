<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsImport;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersImport;
use Oro\Bundle\WorkflowBundle\Model\ProcessImport;

class ProcessImportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessDefinitionsImport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $definitionImport;

    /**
     * @var ProcessTriggersImport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggersImport;

    /**
     * @var ProcessImport
     */
    protected $processImport;

    protected function setUp()
    {
        $this->definitionImport = $this->getMockBuilder(
            'Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsImport'
        )->disableOriginalConstructor()->getMock();

        $this->triggersImport = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersImport')
            ->disableOriginalConstructor()
            ->getMock();
        $this->processImport = new ProcessImport($this->definitionImport, $this->triggersImport);
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

        $this->definitionImport->expects($this->once())->method('import')->with(['...definitions config'])
            ->willReturn($definitionsImported);

        //definitions repository mock

        $definitionsRepositoryMock = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();
        
        $this->definitionImport->expects($this->once())->method('getDefinitionsRepository')
            ->willReturn($definitionsRepositoryMock);
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
}
