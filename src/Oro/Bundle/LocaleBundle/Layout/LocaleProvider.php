<?php

namespace Oro\Bundle\LocaleBundle\Layout;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * Provides localized values for layout rendering.
 *
 * This provider acts as a facade to the {@see LocalizationHelper}, offering a convenient way to
 * retrieve the appropriate localized value from a collection of {@see LocalizedFallbackValue} entities
 * based on the current localization context. It is primarily used in layout templates and
 * layout-related code to access localized content (such as titles, descriptions, etc.)
 * with automatic fallback to default values when localization-specific values are not available.
 */
class LocaleProvider
{
    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

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
