<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

/**
 * Returns default config localization
 */
class ConfigDefaultLocalizationProvider implements LocalizationProviderInterface
{
    /** @var LocalizationManager */
    private $localizationManager;

    /**
     * @param LocalizationManager $localizationManager
     */
    public function __construct(LocalizationManager $localizationManager)
    {
        $this->localizationManager = $localizationManager;
    }

    /**
     * @return Localization
     */
    public function getCurrentLocalization()
    {
        return $this->localizationManager->getDefaultLocalization();
    }
}
