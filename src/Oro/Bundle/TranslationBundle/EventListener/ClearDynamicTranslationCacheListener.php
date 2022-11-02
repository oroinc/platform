<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Clears the dynamic translations cache after translations, languages or localizations are changed in the database.
 */
class ClearDynamicTranslationCacheListener implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private array $scheduledLocales = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onTranslationChanged(Translation $translation): void
    {
        $this->scheduleLanguageEntity($translation->getLanguage());
    }

    public function onLanguageChanged(Language $language): void
    {
        $this->scheduleLanguageEntity($language);
    }

    public function onLocalizationChanged(Localization $localization): void
    {
        $this->scheduleLanguageEntity($localization->getLanguage());
    }

    public function postFlush(): void
    {
        if (!$this->scheduledLocales) {
            return;
        }

        try {
            $this->getDynamicTranslationCache()->delete(array_keys($this->scheduledLocales));
        } finally {
            $this->scheduledLocales = [];
        }
    }

    public function onClear(): void
    {
        if ($this->scheduledLocales) {
            $this->scheduledLocales = [];
        }
    }

    private function scheduleLanguageEntity(?Language $languageEntity): void
    {
        if (null === $languageEntity) {
            return;
        }
        $languageCode = $languageEntity->getCode();
        if (isset($this->scheduledLocales[$languageCode])) {
            return;
        }
        $this->scheduledLocales[$languageCode] = true;
    }

    public static function getSubscribedServices(): array
    {
        return [
            DynamicTranslationCache::class
        ];
    }

    private function getDynamicTranslationCache(): DynamicTranslationCache
    {
        return $this->container->get(DynamicTranslationCache::class);
    }
}
