<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

/**
 * Provides methods to work with localized fallback values.
 */
trait FallbackTrait
{
    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization|null                   $localization
     *
     * @return LocalizedFallbackValue|null
     */
    protected function getFallbackValue(Collection $values, Localization $localization = null)
    {
        return $this->getLocalizedFallbackValue($values, $localization);
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     *
     * @return LocalizedFallbackValue|null
     */
    protected function getDefaultFallbackValue(Collection $values)
    {
        return $this->getLocalizedFallbackValue($values);
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param string                              $value
     *
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
     * @param Localization                        $localization
     *
     * @return LocalizedFallbackValue|null
     *
     * @throws \LogicException
     */
    private function getLocalizedFallbackValue(Collection $values, Localization $localization = null)
    {
        $value = $this->getValue($values, $localization);
        if (null !== $localization) {
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

        if (null === $value && null !== $localization) {
            // get default value
            $value = $this->getLocalizedFallbackValue($values);
        }

        return $value;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization|null                   $localization
     *
     * @return LocalizedFallbackValue|null
     *
     * @throws \LogicException
     */
    private function getValue(Collection $values, Localization $localization = null)
    {
        $result = null;
        foreach ($values as $value) {
            $valueLocalization = $value->getLocalization();
            if ($valueLocalization === $localization
                || (
                    null !== $valueLocalization
                    && null !== $localization
                    && $localization->getId() === $valueLocalization->getId()
                )
            ) {
                if (null !== $result) {
                    throw new \LogicException(sprintf(
                        'There must be only one localized fallback value for "%s" localization.',
                        $localization ? $localization->getName() : Localization::DEFAULT_LOCALIZATION
                    ));
                }
                $result = $value;
            }
        }

        return $result;
    }
}
