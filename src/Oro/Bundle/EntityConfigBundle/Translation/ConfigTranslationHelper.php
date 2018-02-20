<?php

namespace Oro\Bundle\EntityConfigBundle\Translation;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @param string|null $locale
     */
    public function invalidateCache($locale = null)
    {
        $this->translationManager->invalidateCache($locale);
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

        foreach ($translations as $key => $value) {
            $this->translationManager->saveTranslation(
                $key,
                $value,
                $locale,
                TranslationManager::DEFAULT_DOMAIN,
                Translation::SCOPE_UI
            );
        }

        // mark translation cache dirty
        $this->translationManager->invalidateCache($locale);

        $this->translationManager->flush();
    }
}
