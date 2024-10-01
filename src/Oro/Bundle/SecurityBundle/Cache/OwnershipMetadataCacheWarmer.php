<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class OwnershipMetadataCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var OwnershipMetadataProviderInterface
     */
    private $provider;

    public function __construct(OwnershipMetadataProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    #[\Override]
    public function warmUp($cacheDir): array
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
