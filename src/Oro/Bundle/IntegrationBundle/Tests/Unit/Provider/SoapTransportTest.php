<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\SoapConnectionException;
use Oro\Bundle\IntegrationBundle\Provider\SOAPTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class SoapTransportTest extends TestCase
{
    /** @var SOAPTransport|\PHPUnit\Framework\MockObject\MockObject */
    protected $transport;

    /** @var Transport|\PHPUnit\Framework\MockObject\MockObject */
    protected $transportEntity;

    /** @var ParameterBag */
    protected $settings;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $soapClientMock;

    /**
     * Setup test entity
     */
    protected function setUp(): void
    {
        $this->transport = $this->getMockForAbstractClass(
            'Oro\Bundle\IntegrationBundle\Provider\SOAPTransport',
            [],
            '',
            true,
            true,
            true,
            ['getSoapClient', 'getSleepBetweenAttempt']
        );

        $this->soapClientMock = $this->getMockBuilder('\SoapClient')
            ->disableOriginalConstructor()
            ->setMethods(['__soapCall', '__getLastResponseHeaders'])
            ->getMock();

        $this->settings        = new ParameterBag();
        $this->transportEntity = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $this->transportEntity->expects($this->any())->method('getSettingsBag')
            ->will($this->returnValue($this->settings));

        $this->transport->expects($this->any())
            ->method('getSleepBetweenAttempt')
            ->will($this->returnValue(1));
    }

    /**
     * Tear down setup objects
     */
    protected function tearDown(): void
    {
        unset($this->transport, $this->transportEntity, $this->soapClientMock, $this->settings);
    }

    /**
     * Test init method
     */
    public function testInit()
    {
        $this->transport->expects($this->once())
            ->method('getSoapClient')
            ->will($this->returnValue($this->soapClientMock));

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

    /**
     * Test init method errors
     */
    public function testInitErrors()
    {
        $this->expectException(\Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException::class);
        $this->transport->init($this->transportEntity);
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testMultipleAttemptException($header, $attempt, $code)
    {
        $this->expectException(SoapConnectionException::class);
        $this->soapClientMock->expects($this->any())
            ->method('__getLastResponseHeaders')
            ->will($this->returnValue($header));
        $this->soapClientMock->expects($this->exactly($attempt))
            ->method('__soapCall')
            ->will($this->throwException(new \Exception('error', $code)));

        $this->transport->expects($this->once())
            ->method('getSoapClient')
            ->will($this->returnValue($this->soapClientMock));

        $this->settings->set('wsdl_url', 'http://localhost.not.exists/?wsdl');
        $this->transport->init($this->transportEntity);
        $this->transport->call('test');
    }

    /**
     * @return array
     */
    public function exceptionProvider()
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
        $this->soapClientMock->expects($this->at(0))
            ->method('__getLastResponseHeaders')
            ->will($this->returnValue("HTTP/1.1 502 Bad gateway\n\r"));
        $this->soapClientMock->expects($this->at(0))
            ->method('__soapCall')
            ->will($this->throwException(new \Exception('error', 502)));

        $this->soapClientMock->expects($this->at(1))
            ->method('__getLastResponseHeaders')
            ->will($this->returnValue("HTTP/1.1 503 Service unavailable Explained\n\r"));
        $this->soapClientMock->expects($this->at(1))
            ->method('__soapCall')
            ->will($this->throwException(new \Exception('error', 503)));

        $this->soapClientMock->expects($this->at(2))
            ->method('__getLastResponseHeaders')
            ->will($this->returnValue("HTTP/1.1 504 Gateway timeout Explained\n\r"));
        $this->soapClientMock->expects($this->at(2))
            ->method('__soapCall')
            ->will($this->throwException(new \Exception('error', 504)));

        $this->soapClientMock->expects($this->at(4))
            ->method('__getLastResponseHeaders')
            ->will($this->returnValue("HTTP/1.1 200 OK\n\r"));
        $this->soapClientMock->expects($this->at(4))
            ->method('__soapCall');

        $this->transport->expects($this->once())
            ->method('getSoapClient')
            ->will($this->returnValue($this->soapClientMock));

        $this->settings->set('wsdl_url', 'http://localhost.not.exists/?wsdl');
        $this->transport->init($this->transportEntity);
        $this->transport->call('test');
    }
}
