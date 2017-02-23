<?php

namespace Oro\Bundle\LocaleBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

class LocalizationCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var LocalizationManager
     */
    private $localizationManager;

    /**
     * @param LocalizationManager $localizationManager
     */
    public function __construct(LocalizationManager $localizationManager)
    {
        $this->localizationManager = $localizationManager;
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
        $this->localizationManager->warmUpCache();
    }
}
