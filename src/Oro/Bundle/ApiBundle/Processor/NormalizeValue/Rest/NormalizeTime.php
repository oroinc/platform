<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Oro\Bundle\ApiBundle\Model\NormalizedDateTime;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\AbstractProcessor;

/**
 * Converts a string to DateTime object (only time part).
 * Provides a regular expression that can be used to validate that a string represents a time value.
 */
class NormalizeTime extends AbstractProcessor
{
    public const REQUIREMENT = '\d{2}(:\d{2}(:\d{2}(\.\d+)?)?)?';

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'time';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'times';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
    protected function normalizeValue(mixed $value): mixed
    {
        $precision = NormalizedDateTime::PRECISION_SECOND;
        $delimiterCount = substr_count($value, ':');
        if (0 === $delimiterCount) {
            $value .= ':00:00';
            $precision = NormalizedDateTime::PRECISION_HOUR;
        } elseif (1 === $delimiterCount) {
            $value .= ':00';
            $precision = NormalizedDateTime::PRECISION_MINUTE;
        }

        $result = new NormalizedDateTime('1970-01-01T' . $value, new \DateTimeZone('UTC'));
        $result->setPrecision($precision);

        return $result;
    }
}
