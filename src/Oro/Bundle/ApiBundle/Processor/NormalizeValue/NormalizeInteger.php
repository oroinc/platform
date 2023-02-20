<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to integer.
 * Provides a regular expression that can be used to validate that a string represents a integer value.
 */
class NormalizeInteger extends AbstractProcessor
{
    private const REQUIREMENT = '-?\d+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString(): string
    {
        return 'integer';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString(): string
    {
        return 'integers';
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
        if (((string)$normalizedValue) !== $value) {
            throw new \UnexpectedValueException(sprintf(
                'Expected %s value. Given "%s".',
                $this->getDataTypeString(),
                $value
            ));
        }

        return $normalizedValue;
    }
}
