<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;

/**
 * Clears the dynamic translations cache after the translations import.
 */
class ClearDynamicTranslationCacheImportListener
{
    private DynamicTranslationCache $dynamicTranslationCache;
    private string $jobName;

    public function __construct(DynamicTranslationCache $dynamicTranslationCache, string $jobName)
    {
        $this->dynamicTranslationCache = $dynamicTranslationCache;
        $this->jobName = $jobName;
    }

    public function onAfterImportTranslations(AfterJobExecutionEvent $event): void
    {
        if (!$event->getJobResult()->isSuccessful()) {
            return;
        }
        $jobExecution = $event->getJobExecution();
        if ($jobExecution->getLabel() !== $this->jobName) {
            return;
        }

        $languageCode = $jobExecution->getExecutionContext()->get('language_code');
        if ($languageCode) {
            $this->dynamicTranslationCache->delete([$languageCode]);
        }
    }
}
