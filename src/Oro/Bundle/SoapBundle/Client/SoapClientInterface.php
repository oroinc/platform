<?php

namespace Oro\Bundle\SoapBundle\Client;

use Oro\Bundle\SoapBundle\Client\Settings\SoapClientSettingsInterface;

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
