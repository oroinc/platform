<?php

namespace Oro\Bundle\DataGridBundle\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration as SorterConfiguration;

/**
 * Returns an array of field names (required by applied sorters) which must be present in select statement of
 * datasource query.
 */
class SelectedFieldsFromSortersProvider extends AbstractSelectedFieldsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getConfiguration(DatagridConfiguration $datagridConfiguration): array
    {
        return $datagridConfiguration->offsetGetByPath(SorterConfiguration::COLUMNS_PATH, []);
    }
}
