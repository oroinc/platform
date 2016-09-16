<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesProvider
{
    /** @var ActionProcessorInterface */
    protected $processor;

    /** @var ResourcesCache */
    protected $resourcesCache;

    /** @var array */
    protected $accessibleResources;

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
     * Gets a configuration of all resources for a given Data API version.
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
        $context->getRequestType()->set($requestType);

        $this->processor->process($context);

        $resources = array_values($context->getResult()->toArray());
        $this->resourcesCache->saveResources(
            $version,
            $requestType,
            $resources,
            $context->getAccessibleResources()
        );

        return $resources;
    }

    /**
     * Gets a list of resources accessible through Data API.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return string[] The list of class names
     */
    public function getAccessibleResources($version, RequestType $requestType)
    {
        $result = [];
        $accessibleResources = $this->loadAccessibleResources($version, $requestType);
        foreach ($accessibleResources as $entityClass => $isAccessible) {
            if ($isAccessible) {
                $result[] = $entityClass;
            }
        }

        return $result;
    }

    /**
     * Checks whether a given entity is accessible through Data API.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceAccessible($entityClass, $version, RequestType $requestType)
    {
        $accessibleResources = $this->loadAccessibleResources($version, $requestType);

        return
            array_key_exists($entityClass, $accessibleResources)
            && $accessibleResources[$entityClass];
    }

    /**
     * Checks whether a given entity is configured to be used in Data API.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceKnown($entityClass, $version, RequestType $requestType)
    {
        $accessibleResources = $this->loadAccessibleResources($version, $requestType);

        return array_key_exists($entityClass, $accessibleResources);
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [entity class => accessible flag]
     */
    protected function loadAccessibleResources($version, RequestType $requestType)
    {
        if (null === $this->accessibleResources) {
            $this->accessibleResources = $this->resourcesCache->getAccessibleResources($version, $requestType);
            if (null === $this->accessibleResources) {
                $this->getResources($version, $requestType);
                $this->accessibleResources = $this->resourcesCache->getAccessibleResources($version, $requestType);
            }
        }

        return $this->accessibleResources;
    }
}
