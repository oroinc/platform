<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestTwoWayConnector as TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestContext;

class ReverseSyncProcessorTest extends \PHPUnit_Framework_TestCase
{

    /** @var Channel|\PHPUnit_Framework_MockObject_MockObject */
    protected $channel;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $processorRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $jobExecutor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $log;

    /**
     * Setup test obj and mock
     */
    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('createQueryBuilder', 'getRepository'))
            ->getMock();

        $this->processorRegistry = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry');

        $this->jobExecutor = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry');
        $this->channel  = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->log      = $this->getMock('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy');

        $this->log->expects($this->any())
            ->method('info')
            ->will($this->returnValue(''));
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        unset($this->em, $this->processorRegistry, $this->registry, $this->jobExecutor, $this->processor, $this->log);
    }

    /**
     * Return mocked sync processor
     *
     * @param array $mockedMethods
     * @return \PHPUnit_Framework_MockObject_MockObject|ReverseSyncProcessor
     */
    protected function getReverseSyncProcessor($mockedMethods = [])
    {
        return $this->getMock(
            'Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor',
            $mockedMethods,
            [
                $this->em,
                $this->processorRegistry,
                $this->jobExecutor,
                $this->registry,
                $this->log
            ]
        );
    }

    /**
     * Test process method
     */
    public function testProcess()
    {
        $connectors = 'test';
        $params = [];
        $realConnector = new TestConnector();

        $this->registry->expects($this->any())
            ->method('getConnectorType')
            ->will($this->returnValue($realConnector));

        $processor = $this->getReverseSyncProcessor(['processExport']);
        $processor->process($this->channel, $connectors, $params);
    }


    public function testOneChannelConnectorProcess()
    {
        $connector = 'testConnector';

        $this->channel->expects($this->never())
            ->method('getConnectors');

        $this->channel->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('testChannel'));

        $realConnector = new TestConnector();

        $this->registry->expects($this->once())
            ->method('getConnectorType')
            ->will($this->returnValue($realConnector));

        $this->em->expects($this->never())
            ->method('getRepository');

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
                        'entityName'     => 'testEntity',
                        'channel'        => 'testChannel',
                        'testParameter'  => 'testValue'
                    ]
                ]
            )
            ->will($this->returnValue($jobResult));

        $processor = new ReverseSyncProcessor(
            $this->em,
            $this->processorRegistry,
            $this->jobExecutor,
            $this->registry,
            $this->log
        );

        $processor->process($this->channel, $connector, ['testParameter' => 'testValue']);
    }
}
