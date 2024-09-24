<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;

/**
 * The filter by a workflow name.
 * Works and looks like entity filter except - expression is not added to the datasource query because it does not
 * contain a join for workflow, so it would not work correctly. Actual filtering is handled in
 * {@see \Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener}.
 */
class WorkflowNameFilter extends EntityFilter
{
    #[\Override]
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        return false;
    }
}
