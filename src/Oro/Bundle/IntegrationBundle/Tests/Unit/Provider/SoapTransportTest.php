<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Exception\SoapConnectionException;
use Oro\Bundle\IntegrationBundle\Provider\SOAPTransport;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Component\HttpFoundation\ParameterBag;

class SoapTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var SOAPTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var Transport|\PHPUnit\Framework\MockObject\MockObject */
    private $transportEntity;

    /** @var ParameterBag */
    private $settings;

    /** @var \SoapClient|\PHPUnit\Framework\MockObject\MockObject */
    private $soapClient;

    protected function setUp(): void
    {
        $this->transport = $this->getMockForAbstractClass(
            SOAPTransport::class,
            [],
            '',
            true,
            true,
            true,
            ['getSoapClient', 'getSleepBetweenAttempt']
        );

        $this->soapClient = $this->getMockBuilder(\SoapClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__soapCall', '__getLastResponseHeaders'])
            ->getMock();

        $this->settings = new ParameterBag();
        $this->transportEntity = $this->createMock(Transport::class);
        $this->transportEntity->expects($this->any())
            ->method('getSettingsBag')
            ->willReturn($this->settings);

        $this->transport->expects($this->any())
            ->method('getSleepBetweenAttempt')
            ->willReturn(1);
    }

    public function testInit()
    {
        $this->transport->expects($this->once())
            ->method('getSoapClient')
            ->willReturn($this->soapClient);

        $this->settings->set('wsdl_url', 'http://localhost.not.exists/?wsdl');

        try {
            $this->transport->init($this->transportEntity);
        } catch (\SoapFault $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->transport->call('test');

        $this->assertEmpty($this->transport->getLastRequest());
        $this->assertEmpty($this->transport->getLastResponse());
    }

    public function testInitErrors()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->transport->init($this->transportEntity);
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testMultipleAttemptException(string $header, int $attempt, int $code)
    {
        $this->expectException(SoapConnectionException::class);
        $this->soapClient->expects($this->any())
            ->method('__getLastResponseHeaders')
            ->willReturn($header);
        $this->soapClient->expects($this->exactly($attempt))
            ->method('__soapCall')
            ->willThrowException(new \Exception('error', $code));

        $this->transport->expects($this->once())
            ->method('getSoapClient')
            ->willReturn($this->soapClient);

        $this->settings->set('wsdl_url', 'http://localhost.not.exists/?wsdl');
        $this->transport->init($this->transportEntity);
        $this->transport->call('test');
    }

    public function exceptionProvider(): array
    {
        return [
            'Attempts'              => [
                "HTTP/1.1 502 Bad gateway\n\r",
                4,
                502
            ],
            'Internal server error' => [
                "HTTP/1.1 500 Internal server error\n\r",
                1,
                500
            ]
        ];
    }

    public function testMultipleAttempt()
    {
        $this->soapClient->expects($this->exactly(4))
            ->method('__soapCall')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function () {
                    throw new \Exception('error', 502);
                }),
                new ReturnCallback(function () {
                    throw new \Exception('error', 503);
                }),
                new ReturnCallback(function () {
                    throw new \Exception('error', 504);
                }),
                new ReturnCallback(function () {
                })
            );
        $this->soapClient->expects($this->exactly(3))
            ->method('__getLastResponseHeaders')
            ->willReturnOnConsecutiveCalls(
                "HTTP/1.1 502 Bad gateway\n\r",
                "HTTP/1.1 503 Service unavailable Explained\n\r",
                "HTTP/1.1 504 Gateway timeout Explained\n\r"
            );

        $this->transport->expects($this->once())
            ->method('getSoapClient')
            ->willReturn($this->soapClient);

        $this->settings->set('wsdl_url', 'http://localhost.not.exists/?wsdl');
        $this->transport->init($this->transportEntity);
        $this->transport->call('test');
    }
}
