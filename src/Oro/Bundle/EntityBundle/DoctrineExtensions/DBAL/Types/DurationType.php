<?php

namespace Oro\Bundle\EntityBundle\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;

/**
 * Doctrine DBAL type for storing duration values as integers.
 *
 * This type extends the standard integer type and represents duration values
 * (typically in seconds or milliseconds) in the database. It requires SQL comment hints
 * to properly identify the column type in database introspection.
 */
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
