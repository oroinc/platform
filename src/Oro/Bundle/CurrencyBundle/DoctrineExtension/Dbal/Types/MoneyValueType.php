<?php

namespace Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\DBAL\Types\MoneyType;

class MoneyValueType extends MoneyType
{
    const TYPE = 'money_value';

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
