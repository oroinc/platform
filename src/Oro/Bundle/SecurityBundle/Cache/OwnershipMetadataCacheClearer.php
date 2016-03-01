<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

class OwnershipMetadataCacheClearer implements CacheClearerInterface
{
    /**
     * @var MetadataProviderInterface
     */
    private $provider;

    /**
     * @param MetadataProviderInterface $provider
     */
    public function __construct(MetadataProviderInterface $provider)
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
