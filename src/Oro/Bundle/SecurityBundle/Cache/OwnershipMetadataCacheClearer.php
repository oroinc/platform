<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class OwnershipMetadataCacheClearer implements CacheClearerInterface
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
    public function clear($cacheDir)
    {
        $this->provider->clearCache();
    }
}
