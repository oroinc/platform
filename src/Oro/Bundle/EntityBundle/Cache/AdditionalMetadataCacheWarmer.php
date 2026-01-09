<?php

namespace Oro\Bundle\EntityBundle\Cache;

use Oro\Bundle\EntityBundle\ORM\Mapping\AdditionalMetadataProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up the additional entity metadata cache.
 */
class AdditionalMetadataCacheWarmer implements CacheWarmerInterface
{
    /** @var AdditionalMetadataProvider */
    protected $additionalMetadataProvider;

    public function __construct(AdditionalMetadataProvider $additionalMetadataProvider)
    {
        $this->additionalMetadataProvider = $additionalMetadataProvider;
    }

    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        $this->additionalMetadataProvider->warmUpMetadata();
        return [];
    }
}
