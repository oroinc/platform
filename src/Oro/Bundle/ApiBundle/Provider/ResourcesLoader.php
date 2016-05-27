<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesLoader
{
    /** @var ActionProcessorInterface */
    protected $processor;

    /** @var ResourcesCache */
    protected $resourcesCache;

    /**
     * @param ActionProcessorInterface $processor
     * @param ResourcesCache           $resourcesCache
     */
    public function __construct(ActionProcessorInterface $processor, ResourcesCache $resourcesCache)
    {
        $this->processor = $processor;
        $this->resourcesCache = $resourcesCache;
    }

    /**
     * Gets all resources available through a given Data API version.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResource[]
     */
    public function getResources($version, RequestType $requestType)
    {
        $resources = $this->resourcesCache->getResources($version, $requestType);
        if (null !== $resources) {
            return $resources;
        }

        /** @var CollectResourcesContext $context */
        $context = $this->processor->createContext();
        $context->setVersion($version);
        $context->getRequestType()->set($requestType->toArray());

        $this->processor->process($context);

        $resources = array_values($context->getResult()->toArray());
        $this->resourcesCache->save($version, $requestType, $resources);

        return $resources;
    }
}
