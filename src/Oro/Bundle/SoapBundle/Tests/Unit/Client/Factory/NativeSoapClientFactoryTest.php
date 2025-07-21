<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\SoapBundle\Client\Factory\NativeSoapClientFactory;
use PHPUnit\Framework\TestCase;

class NativeSoapClientFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $options = [
            'uri' => 'uri',
            'location' => 'location',
        ];

        $soapClient = (new NativeSoapClientFactory())->create(null, $options);

        self::assertInstanceOf(\SoapClient::class, $soapClient);
    }
}
