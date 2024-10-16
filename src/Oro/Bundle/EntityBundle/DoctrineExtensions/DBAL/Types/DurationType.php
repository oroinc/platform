<?php

namespace Oro\Bundle\EntityBundle\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;

class DurationType extends IntegerType
{
    const TYPE = 'duration';

    #[\Override]
    public function getName()
    {
        return self::TYPE;
    }

    #[\Override]
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
