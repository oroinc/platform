<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;

/**
 * Factory to create Guzzle REST client used for integrations
 */
class GuzzleRestClientFactory implements RestClientFactoryInterface
{
    /**
     * {@inheritdoc}
     * @see \GuzzleHttp\Client::applyOptions
     */
    public function createRestClient($baseUrl, array $defaultOptions)
    {
        return new GuzzleRestClient($baseUrl, $defaultOptions);
    }
}
