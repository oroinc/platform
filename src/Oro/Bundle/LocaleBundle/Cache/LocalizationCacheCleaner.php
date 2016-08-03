<?php

namespace Oro\Bundle\LocaleBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

class LocalizationCacheCleaner implements CacheClearerInterface
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
    public function clear($cacheDir)
    {
        $this->localizationManager->clearCache();
    }
}
