<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Client;

use Oro\Bundle\SoapBundle\Client\Factory\NativeSoapClientFactory;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use Oro\Bundle\SoapBundle\Client\SoapClient;

class SoapClientTest extends \PHPUnit\Framework\TestCase
{
    /** @var NativeSoapClientFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $clientFactory;

    /** @var SoapClient */
    private $client;

    protected function setUp(): void
    {
        $this->clientFactory = $this->createMock(NativeSoapClientFactory::class);

        $this->client = new SoapClient($this->clientFactory);
    }

    public function testSend()
    {
        $wsdlFilePath = null;
        $methodName = '__setSoapHeaders';
        $soapOptions = ['1', '2'];
        $settings = new SoapClientSettings($wsdlFilePath, $methodName, $soapOptions);

        $soapResult = true;
        $soapData = ['1', '2'];

        $soapClient = $this->createMock(\SoapClient::class);
        $soapClient->expects(self::once())
            ->method($methodName)
            ->with($soapData)
            ->willReturn($soapResult);

        $this->clientFactory->expects(self::once())
            ->method('create')
            ->with($wsdlFilePath, $soapOptions)
            ->willReturn($soapClient);

        self::assertSame(
            $soapResult,
            $this->client->send($settings, $soapData)
        );
    }
}
