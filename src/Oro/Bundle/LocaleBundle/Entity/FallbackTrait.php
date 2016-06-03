<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\Localization;

trait FallbackTrait
{
    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization|null $localization
     * @return LocalizedFallbackValue
     */
    protected function getLocalizedFallbackValue(Collection $values, Localization $localization = null)
    {
        $filteredValues = $values->filter(
            function (LocalizedFallbackValue $title) use ($localization) {
                return $localization === $title->getLocalization();
            }
        );

        // TODO: implement with fallback
        if ($filteredValues->count() > 1) {
            //$title = $localization ? $localization->getTitle() : 'default';
            $title = $localization ? $localization->getName() : 'default';
            throw new \LogicException(sprintf('There must be only one %s title', $title));
        }
        $value = $filteredValues->first();
        if (!$value && $localization !== null) {
            $value = $this->getLocalizedFallbackValue($values); // get default value
        }

        return $value;
    }
}
