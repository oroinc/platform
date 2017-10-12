<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to unsigned integer.
 * Provides a regular expression that can be used to validate that a string represents an unsigned integer value.
 */
class NormalizeUnsignedInteger extends AbstractProcessor
{
    const REQUIREMENT = '\d+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'unsigned integer';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'unsigned integers';
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
        $normalizedValue = (int)$value;
        if (((string)$normalizedValue) !== $value || $normalizedValue < 0) {
            throw new \UnexpectedValueException(
                sprintf('Expected %s value. Given "%s".', $this->getDataTypeString(), $value)
            );
        }

        return $normalizedValue;
    }
}
