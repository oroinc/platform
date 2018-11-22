<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

/**
 * Provides a list of all Data API sub-resources available for a specific entity.
 */
class SubresourcesProvider
{
    /** @var ActionProcessorInterface */
    private $processor;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var ResourcesCache */
    private $resourcesCache;

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
    public function getSubresources(
        string $entityClass,
        string $version,
        RequestType $requestType
    ): ?ApiResourceSubresources {
        $entitySubresources = $this->resourcesCache->getSubresources($entityClass, $version, $requestType);
        if (null !== $entitySubresources) {
            return $entitySubresources;
        }

        /** @var CollectSubresourcesContext $context */
        $context = $this->processor->createContext();
        $context->setVersion($version);
        $context->getRequestType()->set($requestType);
        $context->setResources($this->resourcesProvider->getResources($version, $requestType));
        $context->setAccessibleResources($this->resourcesProvider->getAccessibleResources($version, $requestType));

        $this->processor->process($context);

        $subresources = $context->getResult()->toArray();
        $this->resourcesCache->saveSubresources($version, $requestType, array_values($subresources));

        return $subresources[$entityClass] ?? null;
    }

    /**
     * Gets a sub-resource for the given association available through a given Data API version.
     *
     * @param string      $entityClass     The FQCN of an entity
     * @param string      $associationName The name of an association
     * @param string      $version         The Data API version
     * @param RequestType $requestType     The request type, for example "rest", "soap", etc.
     *
     * @return ApiResourceSubresources|null
     */
    public function getSubresource(
        string $entityClass,
        string $associationName,
        string $version,
        RequestType $requestType
    ): ?ApiSubresource {
        $subresources = $this->getSubresources($entityClass, $version, $requestType);
        if (null === $subresources) {
            return null;
        }

        return $subresources->getSubresource($associationName);
    }
}
