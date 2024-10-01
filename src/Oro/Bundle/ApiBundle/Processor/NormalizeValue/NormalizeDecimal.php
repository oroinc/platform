<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to floating point number (string data type).
 * Provides a regular expression that can be used to validate that a string represents a number value.
 */
class NormalizeDecimal extends AbstractProcessor
{
    public const REQUIREMENT = '-?\d*\.?\d+';

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'decimal';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'decimals';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
    protected function normalizeValue(mixed $value): mixed
    {
        $normalizedValue = $value;
        if (str_starts_with($normalizedValue, '.')) {
            $normalizedValue = '0' . $normalizedValue;
        } elseif (str_starts_with($normalizedValue, '-.')) {
            $normalizedValue = '-0' . substr($normalizedValue, 1);
        }

        if (((string)(float)$value) !== $normalizedValue) {
            throw new \UnexpectedValueException(sprintf(
                'Expected %s value. Given "%s".',
                $this->getDataTypeString(),
                $value
            ));
        }

        return $normalizedValue;
    }
}
