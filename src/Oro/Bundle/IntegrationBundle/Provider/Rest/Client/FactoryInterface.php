<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\RestTransportSettingsInterface;

/**
 * Defines the interface for REST client factories.
 *
 * Implementations should create and configure REST client instances based on transport settings
 * provided by integration configurations.
 */
interface FactoryInterface
{
    /**
     * Create REST client instance
     *
     * @param RestTransportSettingsInterface $transportEntity entity with connection settings
     *
     * @return RestClientInterface
     */
    public function getClientInstance(RestTransportSettingsInterface $transportEntity);
}
