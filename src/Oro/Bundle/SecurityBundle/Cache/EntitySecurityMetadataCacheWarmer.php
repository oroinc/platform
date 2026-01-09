<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms cache for entity related security metadata.
 */
class EntitySecurityMetadataCacheWarmer implements CacheWarmerInterface
{
    private EntitySecurityMetadataProvider $provider;

    public function __construct(EntitySecurityMetadataProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {inheritdoc}
     */
    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        $this->provider->warmUpCache();
        return [];
    }

    /**
     * {inheritdoc}
     */
    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }
}
