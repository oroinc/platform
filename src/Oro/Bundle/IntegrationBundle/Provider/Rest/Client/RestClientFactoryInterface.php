<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

/**
 * Defines the contract for creating REST client instances.
 *
 * Implementations of this interface are responsible for instantiating REST client objects
 * configured with a base URL and default options. This factory pattern allows different
 * REST client implementations to be used interchangeably throughout the integration system.
 */
interface RestClientFactoryInterface
{
    /**
     * Create REST client instance
     *
     * @param string $baseUrl
     * @param array $defaultOptions
     *
     * @return RestClientInterface
     */
    public function createRestClient($baseUrl, array $defaultOptions);
}
