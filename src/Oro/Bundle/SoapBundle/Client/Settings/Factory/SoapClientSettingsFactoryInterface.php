<?php

namespace Oro\Bundle\SoapBundle\Client\Settings\Factory;

use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;

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
