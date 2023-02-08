<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Form\DataTransformer\Percent100ToLocalizedStringTransformer;

/**
 * Converts a string to floating point number (float data type) that represents
 * a percentage value multiplied by 100.
 */
class NormalizePercent100 extends NormalizeNumber
{
    /**
     * {@inheritdoc}
     */
    protected function normalizeValue(mixed $value): mixed
    {
        return round(parent::normalizeValue($value) * 100.0, Percent100ToLocalizedStringTransformer::PERCENT_SCALE);
    }
}
