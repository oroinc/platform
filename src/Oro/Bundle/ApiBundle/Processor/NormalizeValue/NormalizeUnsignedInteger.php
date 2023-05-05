<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to unsigned integer.
 * Provides a regular expression that can be used to validate that a string represents an unsigned integer value.
 */
class NormalizeUnsignedInteger extends AbstractProcessor
{
    private const REQUIREMENT = '\d+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString(): string
    {
        return 'unsigned integer';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString(): string
    {
        return 'unsigned integers';
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
        $normalizedValue = (int)$value;
        if (((string)$normalizedValue) !== $value || $normalizedValue < 0) {
            throw new \UnexpectedValueException(sprintf(
                'Expected %s value. Given "%s".',
                $this->getDataTypeString(),
                $value
            ));
        }

        return $normalizedValue;
    }
}
