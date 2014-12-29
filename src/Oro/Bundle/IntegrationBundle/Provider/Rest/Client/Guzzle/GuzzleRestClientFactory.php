<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;

class GuzzleRestClientFactory implements RestClientFactoryInterface
{
    /**
     * {@inheritdoc}
     * @see \Guzzle\Http\Message\RequestFactoryInterface::applyOptions
     */
    public function createRestClient($baseUrl, array $defaultOptions)
    {
        return new GuzzleRestClient($baseUrl, $defaultOptions);
    }
}
