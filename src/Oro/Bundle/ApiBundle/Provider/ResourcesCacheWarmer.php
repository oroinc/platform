<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up Data API resourses and sub-resources caches.
 */
class ResourcesCacheWarmer implements CacheWarmerInterface
{
    /** @var EntityAliasResolverRegistry */
    private $entityAliasResolverRegistry;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var SubresourcesProvider */
    private $subresourcesProvider;

    /** @var array */
    private $requestTypes;

    /**
     * @param EntityAliasResolverRegistry $entityAliasResolverRegistry
     * @param ResourcesProvider           $resourcesProvider
     * @param SubresourcesProvider        $subresourcesProvider
     * @param array                       $requestTypes
     */
    public function __construct(
        EntityAliasResolverRegistry $entityAliasResolverRegistry,
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        array $requestTypes
    ) {
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
        $this->resourcesProvider = $resourcesProvider;
        $this->subresourcesProvider = $subresourcesProvider;
        $this->requestTypes = $requestTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->entityAliasResolverRegistry->warmUpCache();

        foreach ($this->requestTypes as $requestType) {
            $requestType = new RequestType($requestType);
            $resources = $this->resourcesProvider->getResources(Version::LATEST, $requestType);
            foreach ($resources as $resource) {
                $this->subresourcesProvider->getSubresources(
                    $resource->getEntityClass(),
                    Version::LATEST,
                    $requestType
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
