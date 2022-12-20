<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Oro\Bundle\TranslationBundle\Event\InvalidateDynamicTranslationCacheEvent;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;

/**
 * Listener for dumping js translations into files for locales with updated version when dynamic cache is invalidated.
 */
class InvalidateDynamicJsTranslationListener
{
    public function __construct(
        private JsTranslationDumper        $translationDumper,
        private DynamicAssetVersionManager $dynamicAssetVersionManager,
    ) {
    }

    public function onInvalidateDynamicTranslationCache(InvalidateDynamicTranslationCacheEvent $event)
    {
        $this->translationDumper->dumpTranslations($event->getLocales());
        $this->dynamicAssetVersionManager->updateAssetVersion('translations');
    }
}
