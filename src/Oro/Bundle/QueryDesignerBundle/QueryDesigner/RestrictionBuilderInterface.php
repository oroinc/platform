<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;

interface RestrictionBuilderInterface
{
    /**
     * Process filter configuration that prepared by AbstractQueryConverter
     *
     * @param array                              $filters
     * @param GroupingOrmFilterDatasourceAdapter $ds
     */
    public function buildRestrictions(array $filters, GroupingOrmFilterDatasourceAdapter $ds);
}
