<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\TestConnector;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class AbstractConnectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|StepExecution */
    protected $stepExecutionMock;

    /** @var MockObject|Transport */
    protected $transportSettings;

    /** @var TransportInterface|MockObject */
    protected $transportMock;

    protected function setUp(): void
    {
        $this->stepExecutionMock = $this->getMockBuilder(StepExecution::class)
            ->onlyMethods(['getExecutionContext', 'getJobExecution'])
            ->disableOriginalConstructor()
            ->getMock();

        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance->method('getAlias')->willReturn('alias');

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->method('getJobInstance')->willReturn($jobInstance);
        $this->stepExecutionMock->method('getJobExecution')->willReturn($jobExecution);

        $this->transportSettings = $this->getMockForAbstractClass(Transport::class);
        $this->transportMock = $this->createMock(TransportInterface::class);
    }

    protected function tearDown(): void
    {
        unset($this->transportMock, $this->stepExecutionMock);
    }

    /**
     * @dataProvider initializationDataProvider
     *
     * @param MockObject|mixed $transport
     * @param null                                           $source
     * @param bool|string                                    $expectedException
     */
    public function testInitialization($transport, $source = null, $expectedException = false)
    {
        $logger              = new LoggerStrategy(new NullLogger());
        $contextRegistry     = new ContextRegistry();
        $contextMediatorMock = $this->getMockBuilder(ConnectorContextMediator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $integration             = new Integration();
        $integration->setTransport($this->transportSettings);

        $context = $contextRegistry->getByStepExecution($this->stepExecutionMock);
        $contextMediatorMock->expects($this->at(0))
            ->method('getTransport')->with($this->equalTo($context))
            ->willReturn($transport);
        $contextMediatorMock->expects($this->at(1))
            ->method('getChannel')->with($this->equalTo($context))
            ->willReturn($integration);

        /** @var AbstractConnector|MockObject $connector */
        $connector = $this->getMockBuilder(AbstractConnector::class)
            ->onlyMethods(['getConnectorSource'])
            ->setConstructorArgs([$contextRegistry, $logger, $contextMediatorMock])
            ->getMockForAbstractClass();

        if (false !== $expectedException) {
            $this->expectException($expectedException);
        } else {
            $transport->expects($this->once())->method('init')
                ->with($this->equalTo($this->transportSettings));
            $connector->expects($this->once())->method('getConnectorSource')
                ->willReturn($source);
        }

        $connector->setStepExecution($this->stepExecutionMock);
    }

    public function initializationDataProvider()
    {
        return [
            'bad transport given, exception expected'       => [
                false,
                null,
                '\LogicException'
            ],
            'with regular iterator, correct initialization' => [
                $this->createMock(TransportInterface::class),
                $this->createMock('\Iterator')
            ],
            'logger aware iterator should receive logger'   => [
                $this->createMock(TransportInterface::class),
                $this->createMock(\Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\LoggerAwareIteratorSource::class)
            ]
        ];
    }

    /**
     * @param mixed            $transport
     * @param mixed            $stepExecutionMock
     * @param null|Integration $channel
     *
     * @param null             $context
     *
     * @return AbstractConnector
     */
    protected function getConnector($transport, $stepExecutionMock, $channel = null, $context = null)
    {
        $contextRegistryMock = $this->createMock(\Oro\Bundle\ImportExportBundle\Context\ContextRegistry::class);
        $contextMediatorMock = $this
            ->getMockBuilder(ConnectorContextMediator::class)
            ->disableOriginalConstructor()->getMock();

        $transportSettings = $this->getMockForAbstractClass(Transport::class);
        $channel           = $channel ? : new Integration();
        $channel->setTransport($transportSettings);

        $contextMock = $context ? : new Context([]);

        $executionContext = new ExecutionContext();
        $stepExecutionMock->expects($this->any())
            ->method('getExecutionContext')->willReturn($executionContext);

        $contextRegistryMock->expects($this->any())->method('getByStepExecution')
            ->willReturn($contextMock);
        $contextMediatorMock->expects($this->once())
            ->method('getTransport')->with($this->equalTo($contextMock))
            ->willReturn($transport);
        $contextMediatorMock->expects($this->once())
            ->method('getChannel')->with($this->equalTo($contextMock))
            ->willReturn($channel);

        $logger = new LoggerStrategy(new NullLogger());

        return new TestConnector($contextRegistryMock, $logger, $contextMediatorMock);
    }

    public function testGetStatusData()
    {
        $connector = $this->getConnector($this->transportMock, $this->stepExecutionMock);
        $connector->setStepExecution($this->stepExecutionMock);

        $reflection = new \ReflectionMethod(
            TestConnector::class,
            'addStatusData'
        );
        $reflection->setAccessible(true);
        $reflection->invoke($connector, 'key', 'value');

        $context = $this->stepExecutionMock->getExecutionContext();
        $date    = $context->get(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY);

        $this->assertArrayHasKey('key', $date);
        $this->assertSame('value', $date['key']);

        $reflection1 = new \ReflectionMethod(
            TestConnector::class,
            'getStatusData'
        );

        $reflection1->setAccessible(true);
        $result = $reflection1->invoke($connector, 'key', 'value');

        $this->assertSame('value', $result);
    }
}
