<?php

namespace Oro\Bundle\CurrencyBundle\Query;

use Doctrine\ORM\QueryBuilder;

/**
 * Defines the contract for transforming multi-currency field references in queries.
 *
 * Implement this interface to create transformers that convert logical multi-currency
 * field names into the appropriate database column references. This abstraction allows
 * the query layer to work with multi-currency fields without needing to know the
 * underlying database schema details.
 */
interface CurrencyQueryBuilderTransformerInterface
{
    /**
     * @param string       $originalFieldName
     * @param QueryBuilder|null $qb
     * @param null         $rootAlias
     * @param null         $newFieldName
     *
     * @return string
     * @throws \InvalidArgumentException in case
     */
    public function getTransformSelectQuery(
        $originalFieldName,
        ?QueryBuilder $qb = null,
        $rootAlias = null,
        $newFieldName = null
    );

    /**
     * @param string $originalFieldName
     * @param string $rootAlias
     *
     * @return string
     */
    public function getTransformSelectQueryForDataGrid(
        $originalFieldName,
        $rootAlias
    );
}
