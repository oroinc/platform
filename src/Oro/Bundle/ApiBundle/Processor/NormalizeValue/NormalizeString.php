<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to string (actually a value is kept as is
 * because a string value does not required any transformation).
 * Provides a regular expression that can be used to validate a string value.
 */
class NormalizeString extends AbstractProcessor
{
    public const REQUIREMENT = '.+';

    #[\Override]
    protected function getDataTypeString(): string
    {
        return true === $this->getOption('allow_empty') ? 'string' : 'not empty string';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return true === $this->getOption('allow_empty') ? 'strings' : 'not empty strings';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
    protected function processRequirement(NormalizeValueContext $context): void
    {
        $context->setRequirement($this->getRequirement());
    }

    #[\Override]
    protected function normalizeValue(mixed $value): mixed
    {
        return $value;
    }

    #[\Override]
    protected function validateValue(string $value): void
    {
        parent::validateValue($value);
        if (true !== $this->getOption('allow_empty') && '' === trim($value, ' ')) {
            throw new \UnexpectedValueException(sprintf(
                'Expected %s value. Given "%s".',
                $this->getDataTypeString(),
                $value
            ));
        }
    }
}
