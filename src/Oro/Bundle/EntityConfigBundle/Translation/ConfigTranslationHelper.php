<?php

namespace Oro\Bundle\EntityConfigBundle\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class ConfigTranslationHelper
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var DynamicTranslationMetadataCache */
    protected $translationCache;

    /** @var TranslationManager */
    protected $translationManager;

    /**
     * @param TranslatorInterface $translator
     * @param DynamicTranslationMetadataCache $translationCache
     * @param TranslationManager $translationManager
     */
    public function __construct(
        TranslatorInterface $translator,
        DynamicTranslationMetadataCache $translationCache,
        TranslationManager $translationManager
    ) {
        $this->translator = $translator;
        $this->translationCache = $translationCache;
        $this->translationManager = $translationManager;
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function isTranslationEqual($key, $value)
    {
        return $value === $this->translator->trans($key);
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    /**
     * @param array $translations
     */
    public function saveTranslations(array $translations)
    {
        if (!$translations) {
            return;
        }

        $locale = $this->translator->getLocale();
        $entities = [];

        foreach ($translations as $key => $value) {
            $entities[] = $this->createTranslationEntity($key, $value, $locale);
        }

        // mark translation cache dirty
        $this->translationCache->updateTimestamp($locale);

        $this->translationManager->flush($entities);
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $locale
     * @return Translation
     */
    protected function createTranslationEntity($key, $value, $locale)
    {
        return $this->translationManager->saveValue(
            $key,
            $value,
            $locale,
            TranslationRepository::DEFAULT_DOMAIN,
            Translation::SCOPE_UI
        );
    }
}
