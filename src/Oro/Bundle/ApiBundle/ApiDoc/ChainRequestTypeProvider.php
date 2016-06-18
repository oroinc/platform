<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

class ChainRequestTypeProvider implements RequestTypeProviderInterface
{
    /** @var RequestTypeProviderInterface[] */
    protected $providers = [];

    /**
     * {@inheritdoc}
     */
    public function getRequestType()
    {
        $requestType = null;
        foreach ($this->providers as $provider) {
            $requestType = $provider->getRequestType();
            if (null !== $requestType) {
                break;
            }
        }

        return $requestType;
    }

    /**
     * Adds a Data API request type provider to the chain.
     *
     * @param RequestTypeProviderInterface $provider
     */
    public function addProvider(RequestTypeProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }
}
