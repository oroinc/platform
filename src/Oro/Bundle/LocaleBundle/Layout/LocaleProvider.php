<?php

namespace Oro\Bundle\LocaleBundle\Layout;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class LocaleProvider
{
    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param Collection $value
     * @return LocalizedFallbackValue
     */
    public function getLocalizedValue(Collection $value)
    {
        return $this->localizationHelper->getLocalizedValue($value);
    }
}
