<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\AbstractProcessor;

/**
 * Converts a string to DateTime object (only date part).
 * Provides a regular expression that can be used to validate that a string represents a date value.
 */
class NormalizeDate extends AbstractProcessor
{
    const REQUIREMENT = '\d{4}(-\d{2}(-\d{2}?)?)?';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'dates';
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
        return new \DateTime($value, new \DateTimeZone('UTC'));
    }
}
