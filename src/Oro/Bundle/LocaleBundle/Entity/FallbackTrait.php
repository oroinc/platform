<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\Collection;

trait FallbackTrait
{
    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization|null $localization
     *
     * @throws \LogicException
     * @return LocalizedFallbackValue
     */
    protected function getLocalizedFallbackValue(Collection $values, Localization $localization = null)
    {
        $filteredValues = $values->filter(
            function (LocalizedFallbackValue $title) use ($localization) {
                return $localization === $title->getLocalization();
            }
        );

        if ($filteredValues->count() > 1) {
            //$title = $localization ? $localization->getTitle() : 'default';
            $title = $localization ? $localization->getName() : 'default';
            throw new \LogicException(sprintf('There must be only one %s title', $title));
        }

        $value = $filteredValues->first();
        if ($value) {
            switch ($value->getFallback()) {
                case FallbackType::PARENT_LOCALE:
                    $value = $this->getLocalizedFallbackValue($values, $localization->getParentLocale());
                    break;
                case FallbackType::SYSTEM:
                    $value = $this->getLocalizedFallbackValue($values);
                    break;
                default:
                    return $value;
            }
        }

        if (!$value && $localization !== null) {
            $value = $this->getLocalizedFallbackValue($values); // get default value
        }

        return $value;
    }
}
