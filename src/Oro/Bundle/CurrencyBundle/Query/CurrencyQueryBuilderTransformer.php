<?php

namespace Oro\Bundle\CurrencyBundle\Query;

use Doctrine\ORM\QueryBuilder;

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
            throw new \Exception('You must specify original field name for base currency query');
        }
        if (!$rootAlias) {
            $rootAliases = $qb->getRootAliases();
            $rootAlias = array_shift($rootAliases);
        }

        $query = sprintf('%s.%sValue', $rootAlias, $originalFieldName);

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformSelectQueryForDataGrid($originalFieldName, $rootAlias)
    {
        return $this->getTransformSelectQuery($originalFieldName, null, $rootAlias);
    }
}
