<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessTriggersImport;
use Oro\Bundle\WorkflowBundle\Model\ProcessTriggerScheduler;

use Doctrine\Common\Persistence\ManagerRegistry;

class ProcessTriggersImportTest extends \PHPUnit_Framework_TestCase
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
     * @var string|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerEntityClass;

    /**
     * @var ProcessTriggerScheduler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processCronScheduler;

    /**
     * @var ProcessTriggersImport
     */
    protected $processTriggersImport;

    protected function setUp()
    {
        $this->configurationBuilder = $this->getMock(
            'Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder'
        );
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->processCronScheduler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessTriggerScheduler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->triggerEntityClass = 'Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger';
        $this->processTriggersImport = new ProcessTriggersImport(
            $this->configurationBuilder,
            $this->managerRegistry,
            $this->triggerEntityClass,
            $this->processCronScheduler
        );
    }

    public function testImport()
    {
        $triggersConfiguration = '';
        $definitions = '';
        //TODO: add test assertions
        $this->processTriggersImport->import($triggersConfiguration, $definitions);
    }

    public function testGetCreatedSchedules()
    {
        //TODO: add test assertions
        $this->processTriggersImport->getCreatedSchedules();
    }
}
