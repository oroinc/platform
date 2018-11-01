<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up Data API resources and sub-resources caches.
 */
class ResourcesCacheWarmer implements CacheWarmerInterface
{
    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var SubresourcesProvider */
    private $subresourcesProvider;

    /** @var array */
    private $requestTypes;

    /**
     * @param ResourcesProvider    $resourcesProvider
     * @param SubresourcesProvider $subresourcesProvider
     * @param array                $requestTypes
     */
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
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->warmUpCache();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * Clears the cache.
     */
    public function clearCache()
    {
        $this->resourcesProvider->clearCache();
    }

    /**
     * Warms up the cache.
     */
    public function warmUpCache()
    {
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
