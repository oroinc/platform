<?php

namespace Oro\Component\PhpUtils;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;

/**
 * @deprecated since 1.10.
 */
class QueryUtil
{
    const IN = 'in';
    const IN_BETWEEN = 'in_between';

    /**
     * @deprecated use Oro\Component\DoctrineUtils\ORM\QueryUtils::optimizeIntegerValues instead
     *
     * @param int[] $intValues Values usually passed to IN()
     *
     * @return array
     */
    public static function optimizeIntValues(array $intValues)
    {
        return QueryUtils::optimizeIntegerValues($intValues);
    }

    /**
     * @deprecated use Oro\Component\DoctrineUtils\ORM\QueryUtils::generateParameterName instead
     *
     * @param string $prefix
     *
     * @return string
     */
    public static function generateParameterName($prefix)
    {
      return QueryUtils::generateParameterName($prefix);
    }
}
