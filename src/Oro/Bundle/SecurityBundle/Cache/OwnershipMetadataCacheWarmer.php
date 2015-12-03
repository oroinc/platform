<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class OwnershipMetadataCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var OwnershipMetadataProvider
     */
    private $provider;

    /**
     * Constructor
     *
     * @param OwnershipMetadataProvider $provider
     */
    public function __construct(OwnershipMetadataProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->provider->warmUpCache();
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
