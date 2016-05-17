<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

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
        $processConfigurations = '';
        //TODO: add test assertions
        $this->processImport->import($processConfigurations);
    }

    public function testGetCreatedSchedules()
    {
        //TODO: add test assertions
        $this->processImport->getCreatedSchedules();
    }

    public function testGetLoadedTriggers()
    {
        //TODO: add test assertions
        $this->processImport->getLoadedTriggers();
    }

    public function testGetLoadedDefinitions()
    {
        //TODO: add test assertions
        $this->processImport->getLoadedDefinitions();
    }
}
