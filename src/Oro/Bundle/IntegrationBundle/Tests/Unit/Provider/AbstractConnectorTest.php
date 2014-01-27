<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Symfony\Component\HttpKernel\Log\NullLogger;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

class AbstractConnectorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|StepExecution */
    protected $stepExecutionMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Transport */
    protected $transportSettings;

    public function setUp()
    {
        $this->stepExecutionMock = $this->getMockBuilder('Oro\\Bundle\\BatchBundle\\Entity\\StepExecution')
            ->setMethods(['getExecutionContext'])
            ->disableOriginalConstructor()->getMock();
        $this->transportSettings = $this->getMockForAbstractClass('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport');
    }

    public function tearDown()
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
        $channel             = new Channel();
        $channel->setTransport($this->transportSettings);

        $context = $contextRegistry->getByStepExecution($this->stepExecutionMock);
        $contextMediatorMock->expects($this->at(0))
            ->method('getTransport')->with($this->equalTo($context))
            ->will($this->returnValue($transport));
        $contextMediatorMock->expects($this->at(1))
            ->method('getChannel')->with($this->equalTo($context))
            ->will($this->returnValue($channel));

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
        $this->assertAttributeSame($channel, 'channel', $connector);
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
}
