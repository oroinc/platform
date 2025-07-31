<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to unsigned integer.
 * Provides a regular expression that can be used to validate that a string represents an unsigned integer value.
 */
class NormalizeUnsignedInteger extends AbstractProcessor
{
    public const REQUIREMENT = '\d+';

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'unsigned integer';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'unsigned integers';
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
        if (((string)$normalizedValue) !== $value || $normalizedValue < 0) {
            throw new \UnexpectedValueException(\sprintf(
                'Expected %s value. Given "%s".',
                $this->getDataTypeString(),
                $value
            ));
        }

        return $normalizedValue;
    }
}
