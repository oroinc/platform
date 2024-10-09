<?php

namespace Oro\Bundle\AddressBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\AddressTypeTranslation;
use Oro\Bundle\AddressBundle\Entity\CountryTranslation;
use Oro\Bundle\AddressBundle\Entity\RegionTranslation;
use Oro\Bundle\TranslationBundle\Entity\Repository\AbstractTranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepositoryInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Event\AfterCatalogueInitialize;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Fill Gedmo\Translatable dictionaries for Country and Region entities on finalizing translation catalogue build.
 */
class TranslatorCatalogueListener
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    public function onAfterCatalogueInit(AfterCatalogueInitialize $event)
    {
        $catalogue = $event->getCatalogue();

        $this->updateTranslations($catalogue, AddressTypeTranslation::class, 'address_type.');
        $this->updateTranslations($catalogue, CountryTranslation::class, 'country.');
        $this->updateTranslations($catalogue, RegionTranslation::class, 'region.');
    }

    private function updateTranslations(MessageCatalogueInterface $catalogue, string $className, string $prefix)
    {
        if (!in_array('entities', $catalogue->getDomains())) {
            return;
        }
        /** @var AbstractTranslationRepository $repository */
        $dictionaryRepository = $this->getRepository($className);
        $data = $this->getTranslations($catalogue, $prefix);

        foreach ($data as $locale => $translations) {
            if ($locale === Translator::DEFAULT_LOCALE) {
                $dictionaryRepository->updateDefaultTranslations($translations);
            } else {
                $dictionaryRepository->updateTranslations($translations, $locale);
            }
        }
    }

    private function getTranslations(MessageCatalogueInterface $catalogue, string $prefix): array
    {
        $data = [];
        /** @var TranslationRepository $translationsRepository */
        $translationsRepository = $this->getRepository(Translation::class);
        $locales = $this->getLocaleWithAllFallbacks($catalogue);

        foreach ($locales as $locale) {
            $translations = $translationsRepository->findTranslations($prefix, 'entities', $locale);
            $catalogueTranslations = $catalogue->all('entities');

            if ($catalogue->getLocale() === $locale && $catalogueTranslations) {
                $translations = array_merge(
                    $translations,
                    array_filter(
                        $catalogueTranslations,
                        static fn (string $key): bool => str_starts_with($key, $prefix),
                        ARRAY_FILTER_USE_KEY
                    )
                );
            }

            $translationKeys = array_map(
                static fn (string $key): string => str_replace($prefix, '', $key),
                array_keys($translations)
            );

            $data[$locale] = array_combine($translationKeys, $translations);
        }

        return $data;
    }

    /**
     * @param MessageCatalogueInterface $catalogue
     * @return array<string>
     */
    private function getLocaleWithAllFallbacks(MessageCatalogueInterface $catalogue): array
    {
        $locales = [Translator::DEFAULT_LOCALE, $catalogue->getLocale()];

        if ($catalogue->getFallbackCatalogue()) {
            $locales = array_merge(
                $locales,
                $this->getLocaleWithAllFallbacks($catalogue->getFallbackCatalogue())
            );
        }

        return $locales;
    }

    /**
     * @param string $className
     * @return TranslationRepositoryInterface|ObjectRepository
     */
    private function getRepository(string $className): TranslationRepositoryInterface|ObjectRepository
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }
}
