<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Symfony\Component\HttpKernel\Log\NullLogger;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\TestConnector;

class AbstractConnectorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|StepExecution */
    protected $stepExecutionMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Transport */
    protected $transportSettings;

    /** @var TransportInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $transportMock;

    protected function setUp()
    {
        $this->stepExecutionMock = $this->getMockBuilder('Akeneo\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->setMethods(['getExecutionContext', 'getJobExecution'])
            ->disableOriginalConstructor()->getMock();

        $jobExecution = $this->getMock('Akeneo\Bundle\BatchBundle\Entity\JobExecution');
        $jobInstance = $this->getMock('Akeneo\Bundle\BatchBundle\Entity\JobInstance');
        $jobExecution->expects($this->any())
            ->method('getJobInstance')
            ->will($this->returnValue($jobInstance));

        $this->stepExecutionMock->expects($this->any())
            ->method('getJobExecution')
            ->will($this->returnValue($jobExecution));

        $jobInstance->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('alias'));

        $this->transportSettings = $this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport');
        $this->transportMock     = $this->getMock('Oro\\Bundle\\IntegrationBundle\\Provider\\TransportInterface');
    }

    protected function tearDown()
    {
        unset($this->transportMock, $this->stepExecutionMock);
    }

    /**
     * @dataProvider initializationDataProvider
     *
     * @param \PHPUnit_Framework_MockObject_MockObject|mixed $transport
     * @param null                                           $source
     * @param bool|string                                    $expectedException
     */
    public function testInitialization($transport, $source = null, $expectedException = false)
    {
        $logger              = new LoggerStrategy(new NullLogger());
        $contextRegistry     = new ContextRegistry();
        $contextMediatorMock = $this
            ->getMockBuilder('Oro\\Bundle\\IntegrationBundle\\Provider\\ConnectorContextMediator')
            ->disableOriginalConstructor()->getMock();
        $integration             = new Integration();
        $integration->setTransport($this->transportSettings);

        $context = $contextRegistry->getByStepExecution($this->stepExecutionMock);
        $contextMediatorMock->expects($this->at(0))
            ->method('getTransport')->with($this->equalTo($context))
            ->will($this->returnValue($transport));
        $contextMediatorMock->expects($this->at(1))
            ->method('getChannel')->with($this->equalTo($context))
            ->will($this->returnValue($integration));

        $connector = $this->getMockBuilder('Oro\\Bundle\\IntegrationBundle\\Provider\\AbstractConnector')
            ->setMethods(['getConnectorSource'])
            ->setConstructorArgs([$contextRegistry, $logger, $contextMediatorMock])
            ->getMockForAbstractClass();

        if (false !== $expectedException) {
            $this->setExpectedException($expectedException);
        } else {
            $transport->expects($this->once())->method('init')
                ->with($this->equalTo($this->transportSettings));
            $connector->expects($this->once())->method('getConnectorSource')
                ->will($this->returnValue($source));
        }

        $connector->setStepExecution($this->stepExecutionMock);

        $this->assertAttributeSame($transport, 'transport', $connector);
        $this->assertAttributeSame($integration, 'channel', $connector);
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
                $this->getMock('Oro\\Bundle\\IntegrationBundle\\Provider\\TransportInterface'),
                $this->getMock('\Iterator')
            ],
            'logger aware iterator should receive logger'   => [
                $this->getMock('Oro\\Bundle\\IntegrationBundle\\Provider\\TransportInterface'),
                $this->getMock('Oro\\Bundle\\IntegrationBundle\\Tests\Unit\Stub\\LoggerAwareIteratorSource')
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
        $contextRegistryMock = $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextRegistry');
        $contextMediatorMock = $this
            ->getMockBuilder('Oro\\Bundle\\IntegrationBundle\\Provider\\ConnectorContextMediator')
            ->disableOriginalConstructor()->getMock();

        $transportSettings = $this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport');
        $channel           = $channel ? : new Integration();
        $channel->setTransport($transportSettings);

        $contextMock = $context ? : new Context([]);

        $executionContext = new ExecutionContext();
        $stepExecutionMock->expects($this->any())
            ->method('getExecutionContext')->will($this->returnValue($executionContext));

        $contextRegistryMock->expects($this->any())->method('getByStepExecution')
            ->will($this->returnValue($contextMock));
        $contextMediatorMock->expects($this->once())
            ->method('getTransport')->with($this->equalTo($contextMock))
            ->will($this->returnValue($transport));
        $contextMediatorMock->expects($this->once())
            ->method('getChannel')->with($this->equalTo($contextMock))
            ->will($this->returnValue($channel));

        $logger = new LoggerStrategy(new NullLogger());

        return new TestConnector($contextRegistryMock, $logger, $contextMediatorMock);
    }

    public function testGetStatusData()
    {
        $connector = $this->getConnector($this->transportMock, $this->stepExecutionMock);
        $connector->setStepExecution($this->stepExecutionMock);

        $reflection = new \ReflectionMethod(
            '\Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\TestConnector',
            'addStatusData'
        );
        $reflection->setAccessible(true);
        $reflection->invoke($connector, 'key', 'value');

        $context = $this->stepExecutionMock->getExecutionContext();
        $date    = $context->get(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY);

        $this->assertArrayHasKey('key', $date);
        $this->assertSame('value', $date['key']);

        $reflection1 = new \ReflectionMethod(
            '\Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\TestConnector',
            'getStatusData'
        );

        $reflection1->setAccessible(true);
        $result = $reflection1->invoke($connector, 'key', 'value');

        $this->assertSame('value', $result);
    }
}
