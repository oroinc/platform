<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * Provides localization depending on extension set and executed
 */
class CurrentLocalizationProvider implements LocalizationProviderInterface
{
    /** @var iterable<CurrentLocalizationExtensionInterface> */
    private iterable $extensions;

    private LocalizationManager $localizationManager;

    private LocaleAwareInterface $translator;

    private TranslatableListener $translatableListener;

    private ?Localization $currentLocalization = null;

    /**
     * @param iterable<CurrentLocalizationExtensionInterface> $extensions
     */
    public function __construct(
        iterable $extensions,
        LocalizationManager $localizationManager,
        LocaleAwareInterface $translator,
        TranslatableListener $translatableListener
    ) {
        $this->extensions = $extensions;
        $this->localizationManager = $localizationManager;
        $this->translator = $translator;
        $this->translatableListener = $translatableListener;
    }

    #[\Override]
    public function getCurrentLocalization(): ?Localization
    {
        if (null !== $this->currentLocalization) {
            return $this->currentLocalization;
        }

        foreach ($this->extensions as $extension) {
            $localization = $extension->getCurrentLocalization();
            if (null !== $localization) {
                return $localization;
            }
        }

        return null;
    }

    /**
     * Makes the given localization as the current one.
     * When the given localization is NULL then reverts the current localization to a default localization.
     */
    #[\Override]
    public function setCurrentLocalization(?Localization $localization): void
    {
        $this->currentLocalization = $localization;

        $localization = $localization
            ?? $this->getCurrentLocalization()
            ?? $this->localizationManager->getDefaultLocalization();

        if ($localization) {
            $languageCode = $localization->getLanguageCode();
            $localeCode = $localization->getFormattingCode();
        } else {
            $languageCode = Configuration::DEFAULT_LANGUAGE;
            $localeCode = Configuration::DEFAULT_LOCALE;
        }

        \Locale::setDefault($localeCode);
        $this->translatableListener->setTranslatableLocale($languageCode);
        $this->translator->setLocale($languageCode);
    }
}
