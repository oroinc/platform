<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\CollectPublicResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectPublicResourcesProcessor;
use Oro\Bundle\ApiBundle\Request\PublicResource;

class PublicResourcesLoader
{
    /** @var CollectPublicResourcesProcessor */
    protected $processor;

    /**
     * @param CollectPublicResourcesProcessor $processor
     */
    public function __construct(CollectPublicResourcesProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Gets all public resources available for the requested API version.
     *
     * @param string   $version     The version of API
     * @param string[] $requestType The type of API request, for example "rest", "soap", "odata", etc.
     *
     * @return PublicResource[]
     */
    public function getResources($version, array $requestType)
    {
        /** @var CollectPublicResourcesContext $context */
        $context = $this->processor->createContext();
        $context->setVersion($version);
        $context->setRequestType($requestType);

        $this->processor->process($context);

        return array_values($context->getResult()->toArray());
    }
}
