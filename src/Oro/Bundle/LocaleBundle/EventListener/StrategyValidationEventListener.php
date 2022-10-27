<?php

namespace Oro\Bundle\LocaleBundle\EventListener;

use Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Build import errors from validation violation list
 * Change names[0].fallback to names[default].fallback
 */
class StrategyValidationEventListener
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function buildErrors(StrategyValidationEvent $event)
    {
        $violations = $event->getConstraintViolationList();
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            if (!$propertyPath) {
                continue;
            }

            $root = $violation->getRoot();
            if (!$root) {
                continue;
            }

            if (!str_contains($violation->getPropertyPath(), '[')) {
                continue;
            }

            $oldError = $propertyPath.StrategyValidationEvent::DELIMITER.$violation->getMessage();
            $newError = $this->replaceIndexWithKey($root, $propertyPath).
                StrategyValidationEvent::DELIMITER.$violation->getMessage();
            if ($oldError !== $newError) {
                $event->removeError($oldError);
                $event->addError($newError);
            }
        }
    }

    private function replaceIndexWithKey($root, string $propertyPath): string
    {
        if (!str_contains($propertyPath, '[')) {
            return $propertyPath;
        }

        $currentPath = '';
        $newPropertyPath = '';
        $parts = explode('.', $propertyPath);

        foreach ($parts as $key => $part) {
            if ($currentPath) {
                $currentPath .= '.';
            }

            if ($newPropertyPath) {
                $newPropertyPath .= '.';
            }

            $currentPath .= $part;

            $value = $this->getValue($root, $currentPath);
            if (!$value) {
                $newPropertyPath .= $part;

                continue;
            }

            /** @var AbstractLocalizedFallbackValue $value */
            if (!is_a($value, AbstractLocalizedFallbackValue::class, true)) {
                $newPropertyPath .= $part;

                continue;
            }

            if (false !== $this->getLeftPosition($part)) {
                $part = substr($part, 0, $this->getLeftPosition($part)).
                    LocalizationCodeFormatter::formatName($value->getLocalization()).
                    substr($part, $this->getRightPosition($part));
            }

            $newPropertyPath .= $part;
        }

        return $newPropertyPath;
    }

    private function getLeftPosition(string $key)
    {
        if (!str_contains($key, '[')) {
            return false;
        }

        return strpos($key, '[') + 1;
    }

    private function getRightPosition(string $key): int
    {
        if (!str_contains($key, '[')) {
            return false;
        }

        $lft = $this->getLeftPosition($key);
        if (false === $lft) {
            return false;
        }

        return strpos($key, ']', $lft);
    }

    /**
     * @param object|array|null $root
     * @return mixed
     */
    private function getValue($root, string $propertyPath)
    {
        try {
            return $this->propertyAccessor->getValue($root, $propertyPath);
        } catch (UnexpectedTypeException $e) {
        } catch (InvalidPropertyPathException $e) {
        } catch (NoSuchPropertyException $e) {
        }
    }
}
