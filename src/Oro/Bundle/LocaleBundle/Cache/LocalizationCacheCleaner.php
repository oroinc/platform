<?php

namespace Oro\Bundle\LocaleBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class LocalizationCacheCleaner implements CacheClearerInterface
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
     * {inheritdoc}
     */
    public function clear($cacheDir)
    {
        $this->provider->clearCache();
    }
}
