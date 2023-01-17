<?php

namespace Oro\Bundle\TranslationBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

/**
 * Does the following for the translations datagrid:
 * * set Language entity that represents English language to the "en_language" parameter
 */
class TranslationListener
{
    private LanguageProvider $languageProvider;

    public function __construct(LanguageProvider $languageProvider)
    {
        $this->languageProvider = $languageProvider;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $event->getDatagrid()->getParameters()->set('en_language', $this->languageProvider->getDefaultLanguage());
    }
}
