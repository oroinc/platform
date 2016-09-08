<?php

namespace Oro\Bundle\EntityConfigBundle\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class ConfigTranslationHelper
{
    /** @var TranslationManager */
    protected $translationManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslationManager $translationManager
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslationManager $translationManager, TranslatorInterface $translator)
    {
        $this->translationManager = $translationManager;
        $this->translator = $translator;
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
        $this->translationManager->invalidateCache($locale);

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
            TranslationManager::DEFAULT_DOMAIN,
            Translation::SCOPE_UI
        );
    }
}
