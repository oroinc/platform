<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Client\Factory;

use Oro\Bundle\SoapBundle\Client\Factory\NativeSoapClientFactory;

class NativeSoapClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $options = [
            'uri' => 'uri',
            'location' => 'location',
        ];

        $soapClient = (new NativeSoapClientFactory())->create(null, $options);

        self::assertInstanceOf(\SoapClient::class, $soapClient);
    }
}
