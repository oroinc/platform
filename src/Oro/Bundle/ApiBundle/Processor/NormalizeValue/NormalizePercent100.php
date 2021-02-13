<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to floating point number (float data type) that represents
 * a percentage value multiplied by 100.
 */
class NormalizePercent100 extends NormalizeNumber
{
    /**
     * {@inheritdoc}
     */
    protected function normalizeValue($value)
    {
        return parent::normalizeValue($value) * 100.0;
    }
}
