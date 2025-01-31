<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

/**
 * Helper to work with localized values.
 */
class LocalizedValueExtractor
{
    /**
     * @param array $values
     * @param Localization|null $localization
     * @return mixed|null
     */
    public function getLocalizedFallbackValue(array $values, ?Localization $localization = null)
    {
        if (empty($values)) {
            return null;
        }

        $value = $values[$localization?->getId()] ?? null;
        if ($value instanceof FallbackType) {
            switch ($value->getType()) {
                case FallbackType::PARENT_LOCALIZATION:
                    $value = $this->getLocalizedFallbackValue($values, $localization->getParentLocalization());
                    break;
                case FallbackType::SYSTEM:
                    $value = $this->getLocalizedFallbackValue($values);
                    break;
            }
        }

        if (!$value && $localization !== null) {
            $value = $this->getLocalizedFallbackValue($values); // get default value
        }

        return $value;
    }
}
