<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Transport;

/**
 * Interface RestTransportSettingsInterface purpose is provide settings
 * which are required for REST client initialization and used in factories
 */
interface RestTransportSettingsInterface
{
    /**
     * Returns base URL of the REST server
     *
     * @return string
     */
    public function getBaseUrl();

    /**
     * Returns list of options for the REST client (headers, ssl, etc.)
     *
     * @return array
     */
    public function getOptions();
}
