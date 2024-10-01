<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to integer.
 * Provides a regular expression that can be used to validate that a string represents a integer value.
 */
class NormalizeInteger extends AbstractProcessor
{
    public const REQUIREMENT = '-?\d+';

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'integer';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'integers';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
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
