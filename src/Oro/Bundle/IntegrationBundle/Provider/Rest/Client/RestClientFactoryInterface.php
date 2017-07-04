<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

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
