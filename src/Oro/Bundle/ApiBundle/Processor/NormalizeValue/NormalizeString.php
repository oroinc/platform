<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to string (actually a value is kept as is
 * because a string value does not required any transformation).
 * Provides a regular expression that can be used to validate a string value.
 */
class NormalizeString extends AbstractProcessor
{
    private const REQUIREMENT = '.+';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString(): string
    {
        return 'string';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString(): string
    {
        return 'strings';
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
    protected function processRequirement(NormalizeValueContext $context): void
    {
        $context->setRequirement($this->getRequirement());
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue(mixed $value): mixed
    {
        return $value;
    }
}
