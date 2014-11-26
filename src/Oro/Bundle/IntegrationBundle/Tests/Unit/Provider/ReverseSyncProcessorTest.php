<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestTwoWayConnector as TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestContext;

class ReverseSyncProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Integration|\PHPUnit_Framework_MockObject_MockObject */
    protected $integration;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processorRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $jobExecutor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $log;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    public function setUp()
    {
        $this->processorRegistry = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry');
        $this->jobExecutor       = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor')
            ->disableOriginalConstructor()->getMock();
        $this->registry          = $this->getMock('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry');
        $this->integration       = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->log               = $this->getMock('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy');
        $this->eventDispatcher   = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    public function tearDown()
    {
        unset(
            $this->processorRegistry,
            $this->registry,
            $this->jobExecutor,
            $this->processor,
            $this->eventDispatcher,
            $this->log
        );
    }

    /**
     * Test process method
     */
    public function testProcess()
    {
        $connectors    = 'test';
        $params        = [];
        $realConnector = new TestConnector();

        $this->registry->expects($this->any())
            ->method('getConnectorType')
            ->will($this->returnValue($realConnector));

        $processor = $this->getReverseSyncProcessor(['processExport']);
        $processor->process($this->integration, $connectors, $params);
    }

    public function testOneIntegrationConnectorProcess()
    {
        $connector = 'testConnector';

        $this->integration->expects($this->never())
            ->method('getConnectors');

        $this->integration->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('testChannel'));

        $expectedAlias = 'test_alias';
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->with(ProcessorRegistry::TYPE_EXPORT)
            ->will($this->returnValue(array($expectedAlias)));

        $realConnector = new TestConnector();

        $this->registry->expects($this->once())
            ->method('getConnectorType')
            ->will($this->returnValue($realConnector));

        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $jobResult = new JobResult();
        $jobResult->setContext(new TestContext());
        $jobResult->setSuccessful(true);

        $this->jobExecutor->expects($this->once())
            ->method('executeJob')
            ->with(
                'export',
                'tstJobName',
                [
                    'export' => [
                        'entityName'    => 'testEntity',
                        'channel'       => 'testChannel',
                        'processorAlias'=> $expectedAlias,
                        'testParameter' => 'testValue'
                    ]
                ]
            )
            ->will($this->returnValue($jobResult));

        $processor = $this->getReverseSyncProcessor();
        $processor->process($this->integration, $connector, ['testParameter' => 'testValue']);
    }

    /**
     * Return mocked sync processor
     *
     * @param array $mockedMethods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ReverseSyncProcessor
     */
    protected function getReverseSyncProcessor($mockedMethods = null)
    {
        return $this->getMock(
            'Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor',
            $mockedMethods,
            [
                $this->processorRegistry,
                $this->jobExecutor,
                $this->registry,
                $this->eventDispatcher,
                $this->log
            ]
        );
    }
}
