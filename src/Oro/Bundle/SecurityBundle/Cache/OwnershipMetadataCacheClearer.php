<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class OwnershipMetadataCacheClearer implements CacheClearerInterface
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
    public function clear($cacheDir)
    {
        $this->provider->clearCache();
    }
}
