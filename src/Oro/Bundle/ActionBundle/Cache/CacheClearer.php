<?php

namespace Oro\Bundle\ActionBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;

class CacheClearer implements CacheClearerInterface
{
    /** @var ActionConfigurationProvider */
    private $provider;

    /**
     * @param ActionConfigurationProvider $provider
     */
    public function __construct(ActionConfigurationProvider $provider)
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
