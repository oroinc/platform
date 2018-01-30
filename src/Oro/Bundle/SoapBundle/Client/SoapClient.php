<?php

namespace Oro\Bundle\SoapBundle\Client;

use Oro\Bundle\SoapBundle\Client\Factory\NativeSoapClientFactory;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;

class SoapClient implements SoapClientInterface
{
    /**
     * @var NativeSoapClientFactory
     */
    private $clientFactory;

    /**
     * @param NativeSoapClientFactory $clientFactory
     */
    public function __construct(NativeSoapClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function send(SoapClientSettingsInterface $settings, array $data)
    {
        $soapClient = $this->clientFactory->create(
            $settings->getWsdlFilePath(),
            $settings->getSoapOptions()
        );

        return $soapClient->{$settings->getMethodName()}($data);
    }
}
