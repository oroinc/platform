<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessDefinitionsImport;

class ProcessDefinitionsImportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessConfigurationBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationBuilder;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $definitionClass;

    /**
     * @var ProcessDefinitionsImport
     */
    protected $processDefinitionsImport;

    protected function setUp()
    {
        $this->configurationBuilder = $this->getMock(
            'Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder'
        );
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->processDefinitionsImport = new ProcessDefinitionsImport(
            $this->configurationBuilder,
            $this->managerRegistry,
            $this->definitionClass
        );
    }

    public function testImport()
    {
        $definitionsConfiguration = '';
        //TODO: add test assertions
        $this->processDefinitionsImport->import($definitionsConfiguration);
    }

    public function testGetDefinitionsRepository()
    {
        //TODO: add test assertions
        $this->processDefinitionsImport->getDefinitionsRepository();
    }
}
