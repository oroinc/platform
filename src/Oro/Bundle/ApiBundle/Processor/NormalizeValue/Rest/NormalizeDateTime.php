<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\AbstractProcessor;

/**
 * Converts a string to DateTime object.
 * Provides a regular expression that can be used to validate that a string represents a date-time value.
 */
class NormalizeDateTime extends AbstractProcessor
{
    const REQUIREMENT = '\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'datetime';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'datetimes';
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
        // datetime value hack due to the fact that some clients pass + encoded as %20 and not %2B,
        // so it becomes space on symfony side due to parse_str php function in HttpFoundation\Request
        $value = str_replace(' ', '+', $value);
        // The timezone is ignored when DateTime value specifies a timezone (e.g. 2010-01-28T15:00:00+02:00)
        // TODO: should be fixed in BAP-8710. Need to use timezone from system config instead of UTC.
        return new \DateTime($value, new \DateTimeZone('UTC'));
    }
}
