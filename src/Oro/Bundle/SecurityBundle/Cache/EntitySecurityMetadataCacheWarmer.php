<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class EntitySecurityMetadataCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var EntitySecurityMetadataProvider
     */
    private $provider;

    /**
     * Constructor
     *
     * @param EntitySecurityMetadataProvider $provider
     */
    public function __construct(EntitySecurityMetadataProvider $provider)
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
