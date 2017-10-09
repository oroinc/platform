<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to big integer (actually a value is kept a string
 * because PHP does not supports big integers).
 * Provides a regular expression that can be used to validate that a string represents a big integer value.
 */
class NormalizeBigint extends AbstractProcessor
{
    const REQUIREMENT = '-?\d+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'big integer';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'big integers';
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
        return $value;
    }
}
