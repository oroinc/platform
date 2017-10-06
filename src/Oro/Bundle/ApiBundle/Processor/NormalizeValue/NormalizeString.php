<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to string (actually a value is kept as is
 * because a sting value does not required any transformation).
 * Provides a regular expression that can be used to validate a string value.
 */
class NormalizeString extends AbstractProcessor
{
    const REQUIREMENT = '.+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString()
    {
        return 'string';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString()
    {
        return 'strings';
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
    public function processRequirement(NormalizeValueContext $context)
    {
        $context->setRequirement($this->getRequirement());
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue($value)
    {
        return $value;
    }
}
