<?php

namespace Oro\Bundle\CronBundle\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL92Platform;

/**
 * This normalizer is applicable for PostgreSQL 9.2 and higher where command arguments are stored as JSON data type
 */
class Pgsql92CommandArgsNormalizer extends CommandArgsNormalizer
{
    #[\Override]
    public function supports(AbstractPlatform $platform)
    {
        return $platform instanceof PostgreSQL92Platform;
    }

    #[\Override]
    public function quoteArg($value)
    {
        return '"' . $value . '"';
    }
}
