<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Client;

use Oro\Bundle\SoapBundle\Client\Factory\NativeSoapClientFactory;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use Oro\Bundle\SoapBundle\Client\SoapClient;
use PHPUnit\Framework\TestCase;

class SoapClientTest extends TestCase
{
    /**
     * @var NativeSoapClientFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientFactory;

    /**
     * @var SoapClient
     */
    private $client;

    protected function setUp()
    {
        $this->clientFactory = $this->createMock(NativeSoapClientFactory::class);

        $this->client = new SoapClient($this->clientFactory);
    }

    public function testSend()
    {
        $wsdlFilePath = null;
        $methodName = '__setLocation';
        $soapOptions = ['1', '2'];
        $settings = new SoapClientSettings($wsdlFilePath, $methodName, $soapOptions);

        $soapResult = 'result';
        $soapData = ['1', '2'];

        $soapClient = $this->createMock(\SoapClient::class);
        $soapClient
            ->expects(static::once())
            ->method($methodName)
            ->with($soapData)
            ->willReturn($soapResult);

        $this->clientFactory
            ->expects(static::once())
            ->method('create')
            ->with($wsdlFilePath, $soapOptions)
            ->willReturn($soapClient);

        static::assertSame(
            $soapResult,
            $this->client->send($settings, $soapData)
        );
    }
}
