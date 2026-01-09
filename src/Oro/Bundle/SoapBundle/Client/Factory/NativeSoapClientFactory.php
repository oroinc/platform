<?php

namespace Oro\Bundle\SoapBundle\Client\Factory;

/**
 * Factory for creating native PHP {@see \SoapClient} instances.
 *
 * Wraps the native PHP {@see \SoapClient} constructor to provide a factory interface
 * for creating SOAP client instances with specified WSDL files and options.
 */
class NativeSoapClientFactory
{
    /**
     * @param mixed $wsdlFilePath
     * @param array $options
     *
     * @return \SoapClient
     */
    public function create($wsdlFilePath, array $options = []): \SoapClient
    {
        return new \SoapClient($wsdlFilePath, $options);
    }
}
