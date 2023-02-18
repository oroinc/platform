<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up API resources and sub-resources caches.
 */
class ResourcesCacheWarmer implements CacheWarmerInterface
{
    private ResourcesProvider $resourcesProvider;
    private SubresourcesProvider $subresourcesProvider;
    private array $requestTypes;

    public function __construct(
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        array $requestTypes
    ) {
        $this->resourcesProvider = $resourcesProvider;
        $this->subresourcesProvider = $subresourcesProvider;
        $this->requestTypes = $requestTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        $this->warmUpCache();
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * Clears the cache.
     */
    public function clearCache(): void
    {
        $this->resourcesProvider->clearCache();
    }

    /**
     * Warms up the cache.
     */
    public function warmUpCache(): void
    {
        $this->resourcesProvider->clearCache();
        foreach ($this->requestTypes as $aspects) {
            $version = Version::LATEST;
            $requestType = new RequestType($aspects);
            $resources = $this->resourcesProvider->getResources($version, $requestType);
            foreach ($resources as $resource) {
                $this->subresourcesProvider->getSubresources($resource->getEntityClass(), $version, $requestType);
            }
        }
    }
}
