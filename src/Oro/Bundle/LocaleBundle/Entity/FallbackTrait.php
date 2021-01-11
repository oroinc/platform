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
     * @param Collection|AbstractLocalizedFallbackValue[] $values
     * @param Localization|null                           $localization
     *
     * @return AbstractLocalizedFallbackValue|null
     */
    protected function getFallbackValue(Collection $values, Localization $localization = null)
    {
        return $this->getLocalizedFallbackValue($values, $localization);
    }

    /**
     * @param Collection|AbstractLocalizedFallbackValue[] $values
     *
     * @return AbstractLocalizedFallbackValue|null
     */
    protected function getDefaultFallbackValue(Collection $values)
    {
        return $this->getLocalizedFallbackValue($values);
    }

    /**
     * @param Collection|AbstractLocalizedFallbackValue[] $values
     * @param string                                      $value
     * @param string                                      $className
     *
     * @return $this
     */
    protected function setDefaultFallbackValue(
        Collection $values,
        $value,
        string $className = LocalizedFallbackValue::class
    ) {
        $oldValue = $this->getLocalizedFallbackValue($values);
        if ($oldValue && $values->contains($oldValue)) {
            $values->removeElement($oldValue);
        }

        /** @var AbstractLocalizedFallbackValue $newValue */
        $newValue = new $className();
        $newValue->setString($value);

        if (!$values->contains($newValue)) {
            $values->add($newValue);
        }

        return $this;
    }

    /**
     * @param Collection|AbstractLocalizedFallbackValue[] $values
     * @param Localization                                $localization
     *
     * @return AbstractLocalizedFallbackValue|null
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
     * @param Collection|AbstractLocalizedFallbackValue[] $values
     * @param Localization|null                           $localization
     *
     * @return AbstractLocalizedFallbackValue|null
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
                    // The application should not be thrown. Believe that the first fallback value is correct.
                    // All other values are ignored and write error to log.
                    $message = <<<EOF
The value "%s" is incorrect. There must be only one fallback value for localization "%s" within the "%s" class.
EOF;
                    $localization = $localization ? $localization->getName() : Localization::DEFAULT_LOCALIZATION;
                    // error_log is a temporary solution and should be removed in scope of BAP-20337
                    /** @noinspection ForgottenDebugOutputInspection */
                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    @error_log(sprintf($message, $value, $localization, get_class($value)).PHP_EOL);
                    break;
                }
                $result = $value;
            }
        }

        return $result;
    }
}
