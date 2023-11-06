<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

/**
 * Clearing LocalizationManager cache during doctrine onClear
 */
class DoctrineEventListener
{
    public function __construct(
        private LocalizationManager $localizationManager
    ) {
    }

    public function onClear(): void
    {
        $this->localizationManager->clearCache();
    }
}
