<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Client\Settings\Factory;

use Oro\Bundle\SoapBundle\Client\Settings\Factory\SoapClientSettingsFactory;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use PHPUnit\Framework\TestCase;

class SoapClientSettingsFactoryTest extends TestCase
{
    public function testCreate()
    {
        $wsdlFilePath = 'path';
        $methodName = 'method';
        $soapOptions = ['1', '2'];

        static::assertEquals(
            new SoapClientSettings($wsdlFilePath, $methodName, $soapOptions),
            (new SoapClientSettingsFactory())->create($wsdlFilePath, $methodName, $soapOptions)
        );
    }
}
