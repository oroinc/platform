<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class LocalizationHelper
{
    use FallbackTrait;

    /**
     * @var LocalizationProvider
     */
    protected $localizationProvider;

    /**
     * @param LocalizationProvider $localizationProvider
     */
    public function __construct(LocalizationProvider $localizationProvider)
    {
        $this->localizationProvider = $localizationProvider;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization|null $localization
     * @return LocalizedFallbackValue
     */
    public function getLocalizedValue(Collection $values, Localization $localization = null)
    {
        return $this->getFallbackValue($values, $localization ?: $this->localizationProvider->getCurrentLocalization());
    }
}
