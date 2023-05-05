<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to floating point number (float data type).
 * Provides a regular expression that can be used to validate that a string represents a number value.
 */
class NormalizeNumber extends AbstractProcessor
{
    private const REQUIREMENT = '-?\d*\.?\d+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString(): string
    {
        return 'number';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString(): string
    {
        return 'numbers';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue(mixed $value): mixed
    {
        $normalizedSrcValue = $value;
        if (str_starts_with($normalizedSrcValue, '.')) {
            $normalizedSrcValue = '0' . $normalizedSrcValue;
        } elseif (str_starts_with($normalizedSrcValue, '-.')) {
            $normalizedSrcValue = '-0' . substr($normalizedSrcValue, 1);
        }

        $normalizedValue = (float)$value;
        if (((string)$normalizedValue) !== $normalizedSrcValue) {
            throw new \UnexpectedValueException(sprintf(
                'Expected %s value. Given "%s".',
                $this->getDataTypeString(),
                $value
            ));
        }

        return $normalizedValue;
    }
}
