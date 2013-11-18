<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @package Oro\Bundle\IntegrationBundle
 */
class SOAPTransport implements TransportInterface
{
    /** @var \SoapClient */
    protected $client;

    /**
     * {@inheritdoc}
     */
    public function init(ParameterBag $settings)
    {
        $wsdlUrl = $settings->get('wsdl_url');
        if ($wsdlUrl) {
            $this->client = $this->getSoapClient($wsdlUrl);
            return true;
        }

        throw new InvalidConfigurationException("SOAP Transport require 'wsdl_url' option to be defined.");
    }

    /**
     * {@inheritdoc}
     */
    public function call($action, $params = [])
    {
        return $this->client->__soapCall($action, $params);
    }

    /**
     * @param string $wsdl_url
     * @return \SoapClient
     */
    protected function getSoapClient($wsdl_url)
    {
        return new \SoapClient($wsdl_url);
    }
}
