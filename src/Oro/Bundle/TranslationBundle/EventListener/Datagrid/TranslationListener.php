<?php

namespace Oro\Bundle\TranslationBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Does the following for the translations datagrid:
 * * set Language entity that represents English language to the "en_language" parameter
 * * add value for "current" column
 */
class TranslationListener
{
    private LanguageProvider $languageProvider;
    private TranslatorInterface $translator;

    public function __construct(LanguageProvider $languageProvider, TranslatorInterface $translator)
    {
        $this->languageProvider = $languageProvider;
        $this->translator = $translator;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $event->getDatagrid()->getParameters()->set('en_language', $this->languageProvider->getDefaultLanguage());
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        $records = $event->getRecords();
        foreach ($records as $record) {
            $record->setValue(
                'current',
                $this->translator->trans(
                    $record->getValue('key'),
                    [],
                    $record->getValue('domain'),
                    $record->getValue('code')
                )
            );
        }
    }
}
