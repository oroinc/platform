<?php

namespace Oro\Bundle\SoapBundle\Client\Factory;

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
