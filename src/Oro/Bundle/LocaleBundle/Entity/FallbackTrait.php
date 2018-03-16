<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

trait FallbackTrait
{
    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization|null $localization
     * @return LocalizedFallbackValue
     */
    protected function getFallbackValue(Collection $values, Localization $localization = null)
    {
        return $this->getLocalizedFallbackValue($values, $localization);
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @return LocalizedFallbackValue
     */
    protected function getDefaultFallbackValue(Collection $values)
    {
        return $this->getLocalizedFallbackValue($values);
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param string $value
     * @return $this
     */
    protected function setDefaultFallbackValue(Collection $values, $value)
    {
        $oldValue = $this->getLocalizedFallbackValue($values);

        if ($oldValue && $values->contains($oldValue)) {
            $values->removeElement($oldValue);
        }
        $newValue = new LocalizedFallbackValue();
        $newValue->setString($value);

        if (!$values->contains($newValue)) {
            $values->add($newValue);
        }

        return $this;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization $localization
     *
     * @throws \LogicException
     * @return LocalizedFallbackValue
     */
    private function getLocalizedFallbackValue(Collection $values, Localization $localization = null)
    {
        $value = $this->getValue($values, $localization);
        if ($localization) {
            if ($value) {
                $fallbackType = $value->getFallback();
            } elseif ($localization->getParentLocalization()) {
                $fallbackType = FallbackType::PARENT_LOCALIZATION;
            } else {
                $fallbackType = FallbackType::SYSTEM;
            }

            switch ($fallbackType) {
                case FallbackType::PARENT_LOCALIZATION:
                    $value = $this->getLocalizedFallbackValue($values, $localization->getParentLocalization());
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

    /**
     * @param Collection $values
     * @param Localization|null $localization
     * @return LocalizedFallbackValue|null
     */
    private function getValue(Collection $values, Localization $localization = null)
    {
        $filteredValues = $values->filter(
            function (LocalizedFallbackValue $title) use ($localization) {
                if ($localization === $title->getLocalization()) {
                    return true;
                } elseif (!$localization || !$title->getLocalization()) {
                    return false;
                }

                return $localization->getId() === $title->getLocalization()->getId();
            }
        );

        if ($filteredValues->count() > 1) {
            $title = $localization ? $localization->getName() : Localization::DEFAULT_LOCALIZATION;
            throw new \LogicException(sprintf('There must be only one %s title', $title));
        }

        return $filteredValues->count() ? $filteredValues->first() : null;
    }
}
