<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

/**
 * Warms up API resourses and sub-resources caches.
 */
class ResourcesCacheWarmer implements CacheWarmerInterface
{
    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var SubresourcesProvider */
    private $subresourcesProvider;

    /** @var array */
    private $requestTypes;

    /**
     * @param EntityAliasResolver  $entityAliasResolver
     * @param ResourcesProvider    $resourcesProvider
     * @param SubresourcesProvider $subresourcesProvider
     * @param array                $requestTypes
     */
    public function __construct(
        EntityAliasResolver $entityAliasResolver,
        ResourcesProvider $resourcesProvider,
        SubresourcesProvider $subresourcesProvider,
        array $requestTypes
    ) {
        $this->entityAliasResolver = $entityAliasResolver;
        $this->resourcesProvider = $resourcesProvider;
        $this->subresourcesProvider = $subresourcesProvider;
        $this->requestTypes = $requestTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->entityAliasResolver->warmUpCache();

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
