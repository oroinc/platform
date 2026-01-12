<?php

namespace Oro\Bundle\SoapBundle\Client\Settings;

/**
 * Defines the contract for SOAP client settings.
 *
 * Provides access to WSDL file path, SOAP method name, and SOAP options required for executing SOAP requests.
 */
interface SoapClientSettingsInterface
{
    /**
     * @return string|null
     */
    public function getWsdlFilePath();

    public function getMethodName(): string;

    public function getSoapOptions(): array;
}
