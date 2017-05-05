<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;

abstract class AbstractRestClientDecoratorFactory implements RestClientFactoryInterface
{
    /**
     * @var RestClientFactoryInterface
     */
    protected $restClientFactory;

    /**
     * @param RestClientFactoryInterface $restClientFactory
     */
    public function __construct(RestClientFactoryInterface $restClientFactory)
    {
        $this->restClientFactory = $restClientFactory;
    }

    /**
     * @return RestClientFactoryInterface
     */
    public function getRestClientFactory()
    {
        return $this->restClientFactory;
    }
}
