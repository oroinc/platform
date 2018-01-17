<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\SoapBundle\Client\Factory\NativeSoapClientFactory;
use PHPUnit\Framework\TestCase;

class NativeSoapClientFactoryTest extends TestCase
{
    public function testCreate()
    {
        $options = [
            'uri' => 'uri',
            'location' => 'location',
        ];

        $soapClient = (new NativeSoapClientFactory())->create(null, $options);

        static::assertInstanceOf(\SoapClient::class, $soapClient);
    }
}
