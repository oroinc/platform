<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

/**
 * Converts a string to a PHP enum value.
 * Provides a regular expression that can be used to validate that a string represents a PHP enum value.
 */
class NormalizeEnum extends AbstractProcessor
{
    public const REQUIREMENT = '\w+';

    #[\Override]
    protected function getDataTypeString(): string
    {
        return 'enum item';
    }

    #[\Override]
    protected function getDataTypePluralString(): string
    {
        return 'enum items';
    }

    #[\Override]
    protected function getRequirement(): string
    {
        return self::REQUIREMENT;
    }

    #[\Override]
    protected function normalizeValue(mixed $value): mixed
    {
        $enumClass = $this->getOption('data_type_detail');
        if (!$enumClass) {
            throw new \LogicException('An enum class was not provided.');
        }

        $normalizedValue = null;
        /** @var \UnitEnum[] $cases */
        $cases = $enumClass::cases();
        foreach ($cases as $case) {
            if ($case->name === $value) {
                $normalizedValue = $case;
                break;
            }
        }
        if (null === $normalizedValue) {
            throw new \UnexpectedValueException(\sprintf('The "%s" enum item is unknown.', $value));
        }

        return $normalizedValue;
    }
}
