<?php

namespace Oro\Bundle\CurrencyBundle\Query;

use Doctrine\ORM\QueryBuilder;

interface CurrencyQueryBuilderTransformerInterface
{
    /**
     * @param string       $originalFieldName
     * @param QueryBuilder $qb
     * @param null         $rootAlias
     * @param null         $newFieldName
     *
     * @return string
     * @throws \InvalidArgumentException in case
     */
    public function getTransformSelectQuery(
        $originalFieldName,
        QueryBuilder $qb = null,
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
