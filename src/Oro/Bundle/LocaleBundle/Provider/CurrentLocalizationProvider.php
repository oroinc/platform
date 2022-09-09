<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;

/**
 * Provides localization depending on extension set and executed
 */
class CurrentLocalizationProvider implements LocalizationProviderInterface
{
    /** @var iterable|CurrentLocalizationExtensionInterface[] */
    private $extensions;

    /** @var Localization|null|bool */
    private $currentLocalization = false;

    /**
     * @param iterable|CurrentLocalizationExtensionInterface[] $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalization()
    {
        if (false !== $this->currentLocalization) {
            return $this->currentLocalization;
        }

        foreach ($this->extensions as $extension) {
            $localization = $extension->getCurrentLocalization();
            if (null !== $localization) {
                return $localization;
            }
        }
    }

    /**
     * Makes the given localization as the current one.
     * When the given localization is NULL then reverts the current localization to a default localization.
     */
    public function setCurrentLocalization(?Localization $localization): void
    {
        $this->currentLocalization = $localization ?? false;
    }
}
