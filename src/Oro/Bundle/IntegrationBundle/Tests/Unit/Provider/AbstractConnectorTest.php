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
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\NullLogger;

class AbstractConnectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var StepExecution|\PHPUnit\Framework\MockObject\MockObject */
    private $stepExecution;

    /** @var Transport|\PHPUnit\Framework\MockObject\MockObject */
    private $transportSettings;

    /** @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    protected function setUp(): void
    {
        $this->stepExecution = $this->createMock(StepExecution::class);

        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance->expects($this->any())
            ->method('getAlias')
            ->willReturn('alias');

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects($this->any())
            ->method('getJobInstance')
            ->willReturn($jobInstance);
        $this->stepExecution->expects($this->any())
            ->method('getJobExecution')
            ->willReturn($jobExecution);

        $this->transportSettings = $this->createMock(Transport::class);
        $this->transport = $this->createMock(TransportInterface::class);
    }

    /**
     * @dataProvider initializationDataProvider
     *
     * @param Transport|\PHPUnit\Framework\MockObject\MockObject $transport
     * @param null                                               $source
     * @param bool|string                                        $expectedException
     */
    public function testInitialization($transport, $source = null, $expectedException = false)
    {
        $logger = new LoggerStrategy(new NullLogger());
        $contextRegistry = new ContextRegistry();
        $contextMediator = $this->createMock(ConnectorContextMediator::class);
        $integration = new Integration();
        $integration->setTransport($this->transportSettings);

        $context = $contextRegistry->getByStepExecution($this->stepExecution);
        $contextMediator->expects($this->once())
            ->method('getTransport')
            ->with($this->identicalTo($context))
            ->willReturn($transport);
        $contextMediator->expects($this->once())
            ->method('getChannel')
            ->with($this->identicalTo($context))
            ->willReturn($integration);

        $connector = $this->getMockBuilder(AbstractConnector::class)
            ->onlyMethods(['getConnectorSource'])
            ->setConstructorArgs([$contextRegistry, $logger, $contextMediator])
            ->getMockForAbstractClass();

        if (false !== $expectedException) {
            $this->expectException($expectedException);
        } else {
            $transport->expects($this->once())
                ->method('init')
                ->with($this->identicalTo($this->transportSettings));
            $connector->expects($this->once())
                ->method('getConnectorSource')
                ->willReturn($source);
        }

        $connector->setStepExecution($this->stepExecution);
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
     * @param mixed            $stepExecution
     * @param null|Integration $channel
     * @param null             $context
     *
     * @return AbstractConnector
     */
    private function getConnector($transport, $stepExecution, $channel = null, $context = null)
    {
        $contextRegistry = $this->createMock(ContextRegistry::class);
        $contextMediator = $this->createMock(ConnectorContextMediator::class);

        $transportSettings = $this->createMock(Transport::class);
        $channel = $channel ?: new Integration();
        $channel->setTransport($transportSettings);

        if (null === $context) {
            $context = new Context([]);
        }

        $executionContext = new ExecutionContext();
        $stepExecution->expects($this->any())
            ->method('getExecutionContext')
            ->willReturn($executionContext);

        $contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->willReturn($context);
        $contextMediator->expects($this->once())
            ->method('getTransport')
            ->with($this->identicalTo($context))
            ->willReturn($transport);
        $contextMediator->expects($this->once())
            ->method('getChannel')
            ->with($this->identicalTo($context))
            ->willReturn($channel);

        $logger = new LoggerStrategy(new NullLogger());

        return new TestConnector($contextRegistry, $logger, $contextMediator);
    }

    public function testGetStatusData()
    {
        $connector = $this->getConnector($this->transport, $this->stepExecution);
        $connector->setStepExecution($this->stepExecution);

        ReflectionUtil::callMethod($connector, 'addStatusData', ['key', 'value']);

        $context = $this->stepExecution->getExecutionContext();
        $date = $context->get(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY);

        $this->assertArrayHasKey('key', $date);
        $this->assertSame('value', $date['key']);

        $this->assertSame('value', ReflectionUtil::callMethod($connector, 'getStatusData', ['key', 'value']));
    }
}
