<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Oro\Bundle\ApiBundle\Model\NormalizedDateTime;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\AbstractProcessor;

/**
 * Converts a string to DateTime object (only date part).
 * Provides a regular expression that can be used to validate that a string represents a date value.
 */
class NormalizeDate extends AbstractProcessor
{
    public const REQUIREMENT = '\d{4}(-\d{2}(-\d{2}?)?)?';

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'date';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'dates';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
    protected function normalizeValue(mixed $value): mixed
    {
        $precision = NormalizedDateTime::PRECISION_DAY;
        $delimiterCount = substr_count($value, '-');
        if (0 === $delimiterCount) {
            $value .= '-01-01';
            $precision = NormalizedDateTime::PRECISION_YEAR;
        } elseif (1 === $delimiterCount) {
            $value .= '-01';
            $precision = NormalizedDateTime::PRECISION_MONTH;
        }

        $result = new NormalizedDateTime($value, new \DateTimeZone('UTC'));
        $result->setPrecision($precision);

        return $result;
    }
}
