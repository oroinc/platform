<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\RequestType;

class SubresourcesProvider
{
    /** @var ActionProcessorInterface */
    protected $processor;

    /** @var ResourcesProvider */
    protected $resourcesProvider;

    /** @var ResourcesCache */
    protected $resourcesCache;

    /**
     * @param ActionProcessorInterface $processor
     * @param ResourcesProvider        $resourcesProvider
     * @param ResourcesCache           $resourcesCache
     */
    public function __construct(
        ActionProcessorInterface $processor,
        ResourcesProvider $resourcesProvider,
        ResourcesCache $resourcesCache
    ) {
        $this->processor = $processor;
        $this->resourcesProvider = $resourcesProvider;
        $this->resourcesCache = $resourcesCache;
    }

    /**
     * Gets an entity sub-resources available through a given Data API version.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return ApiResourceSubresources|null
     */
    public function getSubresources($entityClass, $version, RequestType $requestType)
    {
        $entitySubresources = $this->resourcesCache->getSubresources($entityClass, $version, $requestType);
        if (null !== $entitySubresources) {
            return $entitySubresources;
        }

        /** @var CollectSubresourcesContext $context */
        $context = $this->processor->createContext();
        $context->setVersion($version);
        $context->getRequestType()->set($requestType->toArray());
        $context->setResources($this->resourcesProvider->getResources($version, $requestType));

        $this->processor->process($context);

        $subresources = $context->getResult()->toArray();
        $this->resourcesCache->saveSubresources($version, $requestType, array_values($subresources));

        return isset($subresources[$entityClass])
            ? $subresources[$entityClass]
            : null;
    }
}
