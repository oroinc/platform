<?php

namespace Oro\Bundle\EntityConfigBundle\Translation;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * Contains methods related to translations
 */
class ConfigTranslationHelper
{
    /** @var TranslationManager */
    protected $translationManager;

    /** @var Translator */
    protected $translator;

    public function __construct(TranslationManager $translationManager, Translator $translator)
    {
        $this->translationManager = $translationManager;
        $this->translator = $translator;
    }

    public function isTranslationEqual(string $key, string $value): bool
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

    /**
     * @param string $id
     * @param string $fallback
     * @return string
     */
    public function translateWithFallback(string $id, string $fallback)
    {
        if ($this->translator->hasTrans($id)) {
            return $this->translator->trans($id);
        }

        return $fallback;
    }
}
