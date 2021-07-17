<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

/**
 * Listener for dumping js translations into files for locales which was loaded in the process of loading demo data
 */
class JsTranslationDumpDemoDataListener
{
    /**
     * @var JsTranslationDumper
     */
    private $jsTranslationDumper;

    /**
     * @var LanguageProvider
     */
    private $languageProvider;

    /**
     * @var bool
     */
    private $installed;

    /**
     * @param JsTranslationDumper $jsTranslationDumper
     * @param LanguageProvider $languageProvider
     * @param bool|string|null $installed
     */
    public function __construct(
        JsTranslationDumper $jsTranslationDumper,
        LanguageProvider $languageProvider,
        $installed
    ) {
        $this->jsTranslationDumper = $jsTranslationDumper;
        $this->languageProvider = $languageProvider;
        $this->installed = (bool )$installed;
    }

    /**
     * {@inheritDoc}
     */
    public function onPostLoad(MigrationDataFixturesEvent $event): void
    {
        if ($this->installed && $event->isDemoFixtures()) {
            $this->rebuildLocaleTranslations($event);
        }
    }

    private function rebuildLocaleTranslations(MigrationDataFixturesEvent $event): void
    {
        $rebuildLocales = $this->getRebuildLocales();
        if ($rebuildLocales) {
            $event->log(sprintf('dump js translations files for locales: %s.', implode(', ', $rebuildLocales)));

            $this->jsTranslationDumper->dumpTranslations($rebuildLocales);
        }
    }

    private function getRebuildLocales(): array
    {
        $locales = $this->languageProvider->getAvailableLanguageCodes();

        $rebuildLocales = array_values(array_filter($locales, function (string $locale): bool {
            return !$this->jsTranslationDumper->isTranslationFileExist($locale);
        }));

        return $rebuildLocales;
    }
}
