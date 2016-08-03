<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;

class LocalizationHelper
{
    use FallbackTrait;

    /**
     * @var LocalizationManager
     */
    protected $localizationManager;

    /**
     * @var CurrentLocalizationProvider
     */
    protected $currentLocalizationProvider;

    /**
     * @param LocalizationManager $localizationManager
     * @param CurrentLocalizationProvider $currentLocalizationProvider
     */
    public function __construct(
        LocalizationManager $localizationManager,
        CurrentLocalizationProvider $currentLocalizationProvider
    ) {
        $this->localizationManager = $localizationManager;
        $this->currentLocalizationProvider = $currentLocalizationProvider;
    }

    /**
     * @return Localization
     */
    public function getCurrentLocalization()
    {
        return $this->currentLocalizationProvider->getCurrentLocalization();
    }

    /**
     * @return Localization[]
     */
    public function getLocalizations()
    {
        return $this->localizationManager->getLocalizations();
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization|null $localization
     * @return LocalizedFallbackValue
     */
    public function getLocalizedValue(Collection $values, Localization $localization = null)
    {
        return $this->getFallbackValue($values, $localization ?: $this->getCurrentLocalization());
    }
}
