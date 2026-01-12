<?php

namespace Oro\Bundle\SoapBundle\Client;

use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;

/**
 * Defines the contract for SOAP client implementations.
 *
 * Implementing clients execute SOAP requests by accepting settings and data,
 * and returning the SOAP response from the remote service.
 */
interface SoapClientInterface
{
    /**
     * @param SoapClientSettingsInterface $settings
     * @param array                       $data
     *
     * @return mixed
     */
    public function send(SoapClientSettingsInterface $settings, array $data);
}
