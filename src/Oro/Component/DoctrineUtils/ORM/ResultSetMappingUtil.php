<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMapping;

class ResultSetMappingUtil
{
    /**
     * @param AbstractPlatform $platform
     *
     * @return ResultSetMapping
     */
    public static function createResultSetMapping(AbstractPlatform $platform)
    {
        return new PlatformResultSetMapping($platform);
    }

    /**
     * @param ResultSetMapping $mapping
     * @param string           $alias
     *
     * @return string
     *
     * @throws QueryException
     */
    public static function getColumnNameByAlias(ResultSetMapping $mapping, $alias)
    {
        foreach ($mapping->scalarMappings as $key => $val) {
            if ($alias === $val) {
                return $key;
            }
        }

        throw new QueryException(sprintf('Unknown column alias: %s', $alias));
    }
}
