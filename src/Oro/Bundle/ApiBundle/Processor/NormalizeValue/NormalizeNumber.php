<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to floating point number (float data type).
 * Provides a regular expression that can be used to validate that a string represents a number value.
 */
class NormalizeNumber extends AbstractProcessor
{
    const REQUIREMENT = '-?\d*\.?\d+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'number';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'numbers';
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
        $normalizedSrcValue = $value;
        if (0 === strpos($normalizedSrcValue, '.')) {
            $normalizedSrcValue = '0' . $normalizedSrcValue;
        } elseif (0 === strpos($normalizedSrcValue, '-.')) {
            $normalizedSrcValue = '-0' . substr($normalizedSrcValue, 1);
        }

        $normalizedValue = (float)$value;
        if (((string)$normalizedValue) !== $normalizedSrcValue) {
            throw new \UnexpectedValueException(
                sprintf('Expected %s value. Given "%s".', $this->getDataTypeString(), $value)
            );
        }

        return $normalizedValue;
    }
}
