<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to big integer (actually a value is kept a string
 * because PHP does not supports big integers).
 * Provides a regular expression that can be used to validate that a string represents a big integer value.
 */
class NormalizeBigint extends AbstractProcessor
{
    public const REQUIREMENT = '-?\d+';

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'big integer';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'big integers';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
    protected function normalizeValue(mixed $value): mixed
    {
        return $value;
    }
}
