<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;

/**
 * Checks if there are translations available for download and install for the specified language.
 *
 * If the language translations have been installed already, this condition will always evaluate to true.
 * You can check for available updates with is_language_translation_update_available instead.
 *
 * Usage:
 *
 *  conditions:
 *      '@is_language_translation_install_available': "en_US"
 *
 *  conditions:
 *      '@is_language_translation_install_available': $.language
 */
class IsLanguageTranslationInstallAvailableCondition extends AbstractLanguageCondition
{
    private TranslationMetricsProviderInterface $translationMetricsProvider;

    public function __construct(
        TranslationMetricsProviderInterface $translationMetricsProvider,
        ManagerRegistry $doctrine
    ) {
        $this->translationMetricsProvider = $translationMetricsProvider;
        parent::__construct($doctrine);
    }

    protected function isConditionAllowed($context): bool
    {
        $language = $this->getLanguage($context);
        if (null === $language) {
            return false;
        }

        if ($language->isLocalFilesLanguage()) {
            return false;
        }

        /** If the language translations are already installed, @see IsLanguageTranslationUpdateAvailableCondition */
        if (null !== $language->getInstalledBuildDate()) {
            return false;
        }

        return null !== $this->translationMetricsProvider->getForLanguage($language->getCode());
    }

    public function getName(): string
    {
        return 'is_language_translation_install_available';
    }
}
