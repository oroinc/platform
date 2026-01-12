<?php

namespace Oro\Bundle\SoapBundle\Client\Settings\Factory;

use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;

/**
 * Defines the contract for factories that create SOAP client settings.
 *
 * Implementing factories produce SoapClientSettings instances from WSDL paths, method names, and SOAP options.
 */
interface SoapClientSettingsFactoryInterface
{
    /**
     * @param string|null $wsdlFilePath
     * @param string      $methodName
     * @param array       $soapOptions
     *
     * @return SoapClientSettingsInterface
     */
    public function create(
        $wsdlFilePath,
        string $methodName,
        array $soapOptions = []
    ): SoapClientSettingsInterface;
}
