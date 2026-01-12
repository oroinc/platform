<?php

namespace Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\DBAL\Types\MoneyType;

/**
 * Doctrine DBAL type for monetary values without automatic PHP conversion.
 *
 * This type extends the base {@see MoneyType} but overrides the PHP value conversion
 * to return the raw database value unchanged. This is useful when you need to
 * preserve the exact database representation of monetary values without applying
 * any transformations during hydration.
 */
class MoneyValueType extends MoneyType
{
    public const TYPE = 'money_value';

    #[\Override]
    public function getName()
    {
        return static::TYPE;
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }
}
