<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestContext;

class SyncProcessorTest extends \PHPUnit_Framework_TestCase
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
     * @return \PHPUnit_Framework_MockObject_MockObject|SyncProcessor
     */
    protected function getSyncProcessor($mockedMethods = [])
    {
        return $this->getMock(
            'Oro\Bundle\IntegrationBundle\Provider\SyncProcessor',
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
        $connectors = [];

        $this->channel->expects($this->once())
            ->method('getConnectors')
            ->will($this->returnValue($connectors));

        $processor = $this->getSyncProcessor(['processImport']);

        $processor->process($this->channel);
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
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->will($this->returnValue([]));
        $channelRepo = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($channelRepo));
        $jobResult = new JobResult();
        $jobResult->setContext(new TestContext());
        $jobResult->setSuccessful(true);
        $this->jobExecutor->expects($this->once())
            ->method('executeJob')
            ->with(
                'import',
                'test job',
                [
                    'import' => [
                        'processorAlias' => false,
                        'entityName'     => 'testEntity',
                        'channel'        => 'testChannel',
                        'testParameter'  => 'testValue'
                    ]
                ]
            )
            ->will($this->returnValue($jobResult));
        $channelRepo->expects($this->once())
            ->method('addStatus');

        $processor = new SyncProcessor(
            $this->em,
            $this->processorRegistry,
            $this->jobExecutor,
            $this->registry,
            $this->log
        );

        $processor->process($this->channel, $connector, ['testParameter' => 'testValue']);
    }
}
