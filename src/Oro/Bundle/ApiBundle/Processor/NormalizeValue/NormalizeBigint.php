<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;


class NormalizeBigint extends AbstractProcessor
{
    const REQUIREMENT = '-?\d+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'integer';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'integers';
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