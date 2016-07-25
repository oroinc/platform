<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;

class LocalizationHelper
{
    use FallbackTrait;

    /**
     * @var LocalizationProvider
     */
    protected $localizationProvider;

    /**
     * @var CurrentLocalizationProvider
     */
    protected $currentLocalizationProvider;

    public function __construct(
        LocalizationProvider $localizationProvider,
        CurrentLocalizationProvider $currentLocalizationProvider
    ) {
        $this->localizationProvider = $localizationProvider;
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
        return $this->localizationProvider->getLocalizations();
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
