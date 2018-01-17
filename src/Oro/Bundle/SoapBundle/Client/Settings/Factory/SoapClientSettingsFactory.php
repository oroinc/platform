<?php

namespace Oro\Bundle\SoapBundle\Client\Settings\Factory;

use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettings;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;

class SoapClientSettingsFactory implements SoapClientSettingsFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(
        $wsdlFilePath,
        string $methodName,
        array $soapOptions = []
    ): SoapClientSettingsInterface {
        return new SoapClientSettings($wsdlFilePath, $methodName, $soapOptions);
    }
}
