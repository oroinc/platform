<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Transport;

use Oro\Bundle\MessageQueueBundle\Transport\ConnectionFactory;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\DsnBasedParameters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ConnectionFactoryTest extends TestCase
{
    private DsnBasedParameters $transportParametersBagMock;

    private ServiceLocator $locatorMock;

    protected function setUp(): void
    {
        $this->transportParametersBagMock = self::createMock(DsnBasedParameters::class);
        $this->transportParametersBagMock->method('getTransportName')
            ->willReturn('transport_name');

        $this->locatorMock = self::createMock(ServiceLocator::class);
    }

    public function testTransportConnectionInstanceReturned()
    {
        $transportConnectionMock = self::createMock(ConnectionInterface::class);
        $this->locatorMock->expects(self::once())
            ->method('get')
            ->with($this->transportParametersBagMock->getTransportName())
            ->willReturn($transportConnectionMock);

        self::assertEquals(
            $transportConnectionMock,
            ConnectionFactory::create($this->locatorMock, $this->transportParametersBagMock)
        );
    }

    /**
     * @dataProvider wrongTransportConnectionInstancesProvider
     */
    public function testWrongTransportConnectionInstanceTypeReturned($transportConnection)
    {
        $this->locatorMock->expects(self::once())
            ->method('get')
            ->with($this->transportParametersBagMock->getTransportName())
            ->willReturn($transportConnection);

        $this->expectException(UnexpectedTypeException::class);

        ConnectionFactory::create($this->locatorMock, $this->transportParametersBagMock);
    }

    /**
     * @return array
     */
    public function wrongTransportConnectionInstancesProvider(): array
    {
        return ['scalar' => ['test string'], 'array' => [[]], 'object' => [new \StdClass()]];
    }
}
