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
     * @throws \Exception
     */
    public function getTransformSelectQuery(
        $originalFieldName,
        QueryBuilder $qb = null,
        $rootAlias = null,
        $newFieldName = null
    );

    /**
     * @param QueryBuilder $qb
     * @param $originalFieldName
     * @param $newFieldName
     *
     * @throws \Exception
     */
    public function transformSelect(QueryBuilder $qb, $originalFieldName, $newFieldName);
}
