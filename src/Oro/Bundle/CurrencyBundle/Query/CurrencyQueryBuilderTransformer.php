<?php

namespace Oro\Bundle\CurrencyBundle\Query;

use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class CurrencyQueryBuilderTransformer implements CurrencyQueryBuilderTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTransformSelectQuery(
        $originalFieldName,
        QueryBuilder $qb = null,
        $rootAlias = null,
        $newFieldName = null
    ) {
        if (!$originalFieldName) {
            throw new \InvalidArgumentException('You must specify original field name for base currency query');
        }
        if (!$qb && !$rootAlias) {
            throw new \InvalidArgumentException('You must specify query builder or rootAlias for base currency query');
        }

        if (!$rootAlias) {
            $rootAliases = $qb->getRootAliases();
            $rootAlias = array_shift($rootAliases);
        }

        return QueryBuilderUtil::sprintf('%s.%sValue', $rootAlias, $originalFieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformSelectQueryForDataGrid($originalFieldName, $rootAlias)
    {
        return $this->getTransformSelectQuery($originalFieldName, null, $rootAlias);
    }
}
