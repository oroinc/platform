<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;

/**
 * Works and looks like entity filter except - expression is not added to the datasource query because it does not
 * contain a join for workflow steps, so it would not work correctly. Actual filtering is handled in
 * Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener
 */
class WorkflowStepFilter extends EntityFilter
{
    /**
     * {@inheritdoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        return false;
    }
}
