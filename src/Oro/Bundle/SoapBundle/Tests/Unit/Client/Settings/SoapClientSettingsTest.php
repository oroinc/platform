<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Client\Settings;

use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use PHPUnit\Framework\TestCase;

class SoapClientSettingsTest extends TestCase
{
    public function testAccessors()
    {
        $wsdlFilePath = 'path';
        $methodName = 'method';
        $soapOptions = ['1', '2'];

        $settings = new SoapClientSettings($wsdlFilePath, $methodName, $soapOptions);

        static::assertSame($wsdlFilePath, $settings->getWsdlFilePath());
        static::assertSame($methodName, $settings->getMethodName());
        static::assertSame($soapOptions, $settings->getSoapOptions());
    }
}
