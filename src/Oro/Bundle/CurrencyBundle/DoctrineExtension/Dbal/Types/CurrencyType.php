<?php

namespace Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class CurrencyType extends StringType
{
    const TYPE = 'currency';

    const ISO_CODE_LENGTH = 3;

    #[\Override]
    public function getName()
    {
        return self::TYPE;
    }

    #[\Override]
    public function getDefaultLength(AbstractPlatform $platform)
    {
        return self::ISO_CODE_LENGTH;
    }

    #[\Override]
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
