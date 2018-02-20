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

    /**
     * @param OwnershipMetadataProviderInterface $provider
     */
    public function __construct(OwnershipMetadataProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
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
