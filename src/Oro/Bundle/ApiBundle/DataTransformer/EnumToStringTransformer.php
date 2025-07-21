<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Transforms a PHP enum value to a string.
 */
class EnumToStringTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform(mixed $value, array $config, array $context): mixed
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \UnitEnum) {
            throw new \UnexpectedValueException(\sprintf(
                'Expected a PHP enum value, "%s" given.',
                get_debug_type($value)
            ));
        }

        return $value->name;
    }
}
