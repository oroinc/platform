<?php

namespace Oro\Bundle\EntityBundle\Cache;

use Oro\Bundle\EntityBundle\ORM\Mapping\AdditionalMetadataProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

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
    public function warmUp($cacheDir): array
    {
        $this->additionalMetadataProvider->warmUpMetadata();
        return [];
    }
}
