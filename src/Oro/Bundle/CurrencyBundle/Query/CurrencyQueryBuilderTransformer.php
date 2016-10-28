<?php

namespace Oro\Bundle\CurrencyBundle\Query;

use Doctrine\ORM\QueryBuilder;

class CurrencyQueryBuilderTransformer implements CurrencyQueryBuilderTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transformSelect(QueryBuilder $qb, $originalFieldName, $newFieldName)
    {
        $query = $this->getTransformSelectQuery($originalFieldName, $qb, null, true, $newFieldName);
        $qb->addSelect($query);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformSelectQuery(
        $originalFieldName,
        QueryBuilder $qb = null,
        $rootAlias = null,
        $addAlias = false,
        $newFieldName = null
    ) {
        if (!$originalFieldName) {
            throw new \Exception('You must specify original field name for base currency query');
        }
        if (!$rootAlias) {
            $rootAliases = $qb->getRootAliases();
            $rootAlias = array_shift($rootAliases);
        }

        $query = sprintf('%s.%sValue', $rootAlias, $originalFieldName);

        return $query;
    }
}
