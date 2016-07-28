<?php

namespace Oro\Bundle\LocaleBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class LocalizationCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var LocalizationProvider
     */
    private $provider;

    /**
     * @param LocalizationProvider $provider
     */
    public function __construct(LocalizationProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->provider->warmUpCache();
    }
}
