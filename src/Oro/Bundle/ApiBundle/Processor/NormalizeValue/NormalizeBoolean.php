<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to boolean.
 * Provides a regular expression that can be used to validate that a string represents a boolean value.
 */
class NormalizeBoolean extends AbstractProcessor
{
    public const REQUIREMENT = '0|1|true|True|false|False|yes|Yes|no|No';

    /**
     * {@inheritdoc}
     */
    protected function getDataTypeString(): string
    {
        return 'boolean';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataTypePluralString(): string
    {
        return 'booleans';
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function normalizeValue(mixed $value): mixed
    {
        switch ($value) {
            case 'true':
            case 'True':
            case 'yes':
            case 'Yes':
            case '1':
                return true;
            case 'false':
            case 'False':
            case 'no':
            case 'No':
            case '0':
                return false;
        }

        throw new \UnexpectedValueException(sprintf(
            'Expected %s value. Given "%s".',
            $this->getDataTypeString(),
            $value
        ));
    }
}
