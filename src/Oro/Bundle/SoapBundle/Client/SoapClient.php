<?php

namespace Oro\Bundle\SoapBundle\Client;

use Oro\Bundle\SoapBundle\Client\Factory\NativeSoapClientFactory;
use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;

/**
 * Executes SOAP requests using native PHP {@see \SoapClient}.
 *
 * Accepts SOAP client settings and request data, creates a native {@see \SoapClient} instance,
 * and invokes the specified SOAP method with the provided data, returning the SOAP response.
 */
class SoapClient implements SoapClientInterface
{
    /**
     * @var NativeSoapClientFactory
     */
    private $clientFactory;

    public function __construct(NativeSoapClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    #[\Override]
    public function send(SoapClientSettingsInterface $settings, array $data)
    {
        $soapClient = $this->clientFactory->create(
            $settings->getWsdlFilePath(),
            $settings->getSoapOptions()
        );

        return $soapClient->{$settings->getMethodName()}($data);
    }
}
