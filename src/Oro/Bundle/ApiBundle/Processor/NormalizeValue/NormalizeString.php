<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

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
