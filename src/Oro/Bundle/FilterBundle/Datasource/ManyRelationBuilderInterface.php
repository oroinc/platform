<?php

namespace Oro\Bundle\FilterBundle\Datasource;

interface ManyRelationBuilderInterface
{
    /**
     * Checks if this builder supports the given datasource
     *
     * @param FilterDatasourceAdapterInterface $ds
     *
     * @return bool
     */
    public function supports(FilterDatasourceAdapterInterface $ds);

    /**
     * Builds an expression that allows to apply many-to-many filter
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                     $fieldName
     * @param string                     $parameterName
     * @param string                     $filterName
     * @param bool                       $inverse
     *
     * @return mixed
     */
    public function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $fieldName,
        $parameterName,
        $filterName,
        $inverse = false
    );

    /**
     * Builds an expression that allows to apply "no reference" many-to-many filter
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                     $fieldName
     * @param string                     $filterName
     * @param bool                       $inverse
     *
     * @return mixed
     */
    public function buildNullValueExpr(
        FilterDatasourceAdapterInterface $ds,
        $fieldName,
        $filterName,
        $inverse = false
    );
}
