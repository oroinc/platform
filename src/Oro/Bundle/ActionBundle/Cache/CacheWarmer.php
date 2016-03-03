<?php

namespace Oro\Bundle\ActionBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;

class CacheWarmer implements CacheWarmerInterface
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
    public function warmUp($cacheDir)
    {
        $this->provider->warmUpCache();
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}
