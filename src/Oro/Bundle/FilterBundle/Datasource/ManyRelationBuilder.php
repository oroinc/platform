<?php

namespace Oro\Bundle\FilterBundle\Datasource;

class ManyRelationBuilder
{
    /** @var ManyRelationBuilderInterface[] */
    protected $builders = [];

    /**
     * @param ManyRelationBuilderInterface $builder
     */
    public function addBuilder(ManyRelationBuilderInterface $builder)
    {
        $this->builders[] = $builder;
    }

    /**
     * Builds an expression that allows to apply many-to-many filter
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $fieldName
     * @param string                           $parameterName
     * @param string                           $filterName
     * @param bool                             $inverse
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $fieldName,
        $parameterName,
        $filterName,
        $inverse = false
    ) {
        foreach ($this->builders as $builder) {
            if ($builder->supports($ds)) {
                return $builder->buildComparisonExpr($ds, $fieldName, $parameterName, $filterName, $inverse);
            }
        }

        throw new \RuntimeException(
            sprintf('The "%s" datasource adapter is not supported.', get_class($ds))
        );
    }

    /**
     * Builds an expression that allows to apply "no reference" many-to-many filter
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                     $fieldName
     * @param string                     $filterName
     * @param bool                       $inverse
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function buildNullValueExpr(
        FilterDatasourceAdapterInterface $ds,
        $fieldName,
        $filterName,
        $inverse = false
    ) {
        foreach ($this->builders as $builder) {
            if ($builder->supports($ds)) {
                return $builder->buildNullValueExpr($ds, $fieldName, $filterName, $inverse);
            }
        }

        throw new \RuntimeException(
            sprintf('The "%s" datasource adapter is not supported.', get_class($ds))
        );
    }
}
