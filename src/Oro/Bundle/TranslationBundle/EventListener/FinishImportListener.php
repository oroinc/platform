<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Oro\Bundle\ImportExportBundle\Event\FinishImportEvent;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;

/**
 * Responsible for dumping and updating the translation version.
 */
class FinishImportListener
{
    private const SUPPORTED_ALIASES = [
        'oro_translation_translation.add_or_replace',
        'oro_translation_translation.reset'
    ];

    public function __construct(
        private JsTranslationDumper $translationDumper,
        private DynamicAssetVersionManager $dynamicAssetVersionManager
    ) {
    }

    public function onFinishImport(FinishImportEvent $event): void
    {
        if (!$this->isSupported($event)) {
            return;
        }

        $options = $event->getOptions();
        $languageCode = $options['language_code'];
        $this->translationDumper->dumpTranslations([$languageCode]);
        $this->dynamicAssetVersionManager->updateAssetVersion('translations');
    }

    private function isSupported(FinishImportEvent $event): bool
    {
        $options = $event->getOptions();

        return
            in_array($event->getProcessorAlias(), self::SUPPORTED_ALIASES, true)
            && $event->getType() === ProcessorRegistry::TYPE_IMPORT
            && !empty($options['language_code']);
    }
}
