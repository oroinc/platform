<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;

/**
 * Represents a service to build and apply the query designer filters to a data source.
 */
interface RestrictionBuilderInterface
{
    /**
     * Builds and applies the query designer filters to the given data source.
     *
     * @param array                              $filters
     * @param GroupingOrmFilterDatasourceAdapter $ds
     */
    public function buildRestrictions(array $filters, GroupingOrmFilterDatasourceAdapter $ds): void;
}
