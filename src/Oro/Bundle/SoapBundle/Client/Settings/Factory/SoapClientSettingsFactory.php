<?php

namespace Oro\Bundle\SoapBundle\Client\Settings\Factory;

use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;

/**
 * Factory for creating {@see SoapClientSettings} instances.
 *
 * Creates immutable settings objects that encapsulate WSDL file path, method name,
 * and SOAP options for use with the SoapClient.
 */
class SoapClientSettingsFactory implements SoapClientSettingsFactoryInterface
{
    #[\Override]
    public function create(
        $wsdlFilePath,
        string $methodName,
        array $soapOptions = []
    ): SoapClientSettingsInterface {
        return new SoapClientSettings($wsdlFilePath, $methodName, $soapOptions);
    }
}
