<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Oro\Bundle\ApiBundle\Model\NormalizedDateTime;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\AbstractProcessor;

/**
 * Converts a string to DateTime object.
 * Provides a regular expression that can be used to validate that a string represents a date-time value.
 */
class NormalizeDateTime extends AbstractProcessor
{
    public const REQUIREMENT = '\d{4}(-\d{2}(-\d{2}(T\d{2}(:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?)?';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString(): string
    {
        return 'datetime';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString(): string
    {
        return 'datetimes';
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
        // datetime value hack due to the fact that some clients pass + encoded as %20 and not %2B,
        // so it becomes space on symfony side due to parse_str php function in HttpFoundation\Request
        $value = str_replace(' ', '+', $value);

        $precision = NormalizedDateTime::PRECISION_DAY;
        $delimiterCount = substr_count($value, '-');
        if (0 === $delimiterCount) {
            $value .= '-01-01';
            $precision = NormalizedDateTime::PRECISION_YEAR;
        } elseif (1 === $delimiterCount) {
            $value .= '-01';
            $precision = NormalizedDateTime::PRECISION_MONTH;
        }
        if (str_contains($value, 'T')) {
            $precision = NormalizedDateTime::PRECISION_SECOND;
            $delimiterCount = substr_count($value, ':');
            if (0 === $delimiterCount) {
                $value .= ':00:00';
                $precision = NormalizedDateTime::PRECISION_HOUR;
            } elseif (1 === $delimiterCount) {
                $value .= ':00';
                $precision = NormalizedDateTime::PRECISION_MINUTE;
            }
        }

        // The timezone is ignored when DateTime value specifies a timezone (e.g. 2010-01-28T15:00:00+02:00)
        // This should be fixed in BAP-8710. Need to use timezone from system config instead of UTC.
        $result = new NormalizedDateTime($value, new \DateTimeZone('UTC'));
        $result->setPrecision($precision);

        return $result;
    }
}
