<?php

namespace Oro\Bundle\LocaleBundle\Cache;

use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

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
    public function clear($cacheDir = null)
    {
        $this->localizationManager->clearCache();
    }
}
