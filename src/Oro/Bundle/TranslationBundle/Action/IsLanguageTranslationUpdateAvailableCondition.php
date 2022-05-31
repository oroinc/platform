<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;

/**
 * Checks if there are translation updates available for the specified language.
 *
 * An update is considered new if its last build date is more recent than the last installed build date of the language.
 *
 * New updates will not be checked if translations have not been installed for this language yet.
 * Use is_language_translation_install_available instead.
 *
 * Usage:
 *
 *  conditions:
 *      '@is_language_translation_update_available': "en_US"
 *
 *  conditions:
 *      '@is_language_translation_update_available': $.language
 */
class IsLanguageTranslationUpdateAvailableCondition extends AbstractLanguageCondition
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

        /** If the language translations are not installed yet, @see IsLanguageTranslationInstallAvailableCondition */
        if (null === $language->getInstalledBuildDate()) {
            return false;
        }

        $metrics = $this->translationMetricsProvider->getForLanguage($language->getCode());

        if (null === $metrics) {
            return false;
        }

        return $language->getInstalledBuildDate() < $metrics['lastBuildDate'];
    }

    public function getName(): string
    {
        return 'is_language_translation_update_available';
    }
}
