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
     * Gets all resources available through a given Data API version.
     *
     * @param string   $version     The Data API version
     * @param string[] $requestType The request type, for example "rest", "soap", etc.
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
