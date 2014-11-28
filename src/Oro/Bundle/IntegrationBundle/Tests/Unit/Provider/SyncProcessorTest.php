<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestContext;

class SyncProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Integration|\PHPUnit_Framework_MockObject_MockObject */
    protected $integration;

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

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /**
     * Setup test obj and mock
     */
    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('createQueryBuilder', 'getRepository'))
            ->getMock();

        $this->processorRegistry = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry');

        $this->jobExecutor = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry                = $this->getMock('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry');
        $this->integration             = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->log                     = $this->getMock('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy');
        $this->eventDispatcher         = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    protected function tearDown()
    {
        unset(
            $this->em,
            $this->eventDispatcher,
            $this->processorRegistry,
            $this->registry,
            $this->jobExecutor,
            $this->processor,
            $this->log
        );
    }

    /**
     * Return mocked sync processor
     *
     * @param array $mockedMethods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|SyncProcessor
     */
    protected function getSyncProcessor($mockedMethods = null)
    {
        $repository = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $registry->expects($this->any())->method('getManager')
            ->will($this->returnValue($this->em));
        $registry->expects($this->any())->method('getRepository')
            ->will($this->returnValue($repository));

        return $this->getMock(
            'Oro\Bundle\IntegrationBundle\Provider\SyncProcessor',
            $mockedMethods,
            [
                $registry,
                $this->processorRegistry,
                $this->jobExecutor,
                $this->registry,
                $this->eventDispatcher,
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

        $this->integration->expects($this->once())
            ->method('getConnectors')
            ->will($this->returnValue($connectors));

        $processor = $this->getSyncProcessor(['processImport']);

        $processor->process($this->integration);
    }

    public function testOneChannelConnectorProcess()
    {
        $connector  = 'testConnector';
        $connectors = [$connector];

        $this->integration
            ->expects($this->once())
            ->method('getConnectors')
            ->will($this->returnValue($connectors));

        $this->integration
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('testChannel'));

        $this->integration
            ->expects($this->atLeastOnce())
            ->method('getType')
            ->will($this->returnValue('testChannelType'));

        $this->integration
            ->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $realConnector = new TestConnector();
        $this->registry
            ->expects($this->once())
            ->method('getConnectorType')
            ->will($this->returnValue($realConnector));

        $this->processorRegistry
            ->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->will($this->returnValue([]));

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
                        'channelType'    => 'testChannelType',
                        'testParameter'  => 'testValue'
                    ]
                ]
            )
            ->will($this->returnValue($jobResult));

        $processor = $this->getSyncProcessor();
        $processor->process($this->integration, $connector, ['testParameter' => 'testValue']);
    }
}
