<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\AbstractProcessor;

/**
 * Converts a string to DateTime object (only time part).
 * Provides a regular expression that can be used to validate that a string represents a time value.
 */
class NormalizeTime extends AbstractProcessor
{
    private const REQUIREMENT = '\d{2}:\d{2}(:\d{2}(\.\d+)?)?';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString(): string
    {
        return 'time';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString(): string
    {
        return 'times';
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
        return new \DateTime('1970-01-01T' . $value, new \DateTimeZone('UTC'));
    }
}
