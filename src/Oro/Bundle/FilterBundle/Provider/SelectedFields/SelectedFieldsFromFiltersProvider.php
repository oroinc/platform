<?php

namespace Oro\Bundle\FilterBundle\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\AbstractSelectedFieldsProvider;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration as FilterConfiguration;

/**
 * Returns fields (required by applied filters) which must be present in select statement of datasource query.
 */
class SelectedFieldsFromFiltersProvider extends AbstractSelectedFieldsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getConfiguration(DatagridConfiguration $datagridConfiguration): array
    {
        return $datagridConfiguration->offsetGetByPath(FilterConfiguration::COLUMNS_PATH, []);
    }
}
