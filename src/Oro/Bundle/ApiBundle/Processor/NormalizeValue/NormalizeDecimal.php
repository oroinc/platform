<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to floating point number (string data type).
 * Provides a regular expression that can be used to validate that a string represents a number value.
 */
class NormalizeDecimal extends AbstractProcessor
{
    const REQUIREMENT = '-?\d*\.?\d+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'decimal';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'decimals';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequirement()
    {
        return self::REQUIREMENT;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue($value)
    {
        $normalizedValue = $value;
        if (0 === strpos($normalizedValue, '.')) {
            $normalizedValue = '0' . $normalizedValue;
        } elseif (0 === strpos($normalizedValue, '-.')) {
            $normalizedValue = '-0' . substr($normalizedValue, 1);
        }

        if (((string)(float)$value) !== $normalizedValue) {
            throw new \UnexpectedValueException(
                sprintf('Expected %s value. Given "%s".', $this->getDataTypeString(), $value)
            );
        }

        return $normalizedValue;
    }
}
