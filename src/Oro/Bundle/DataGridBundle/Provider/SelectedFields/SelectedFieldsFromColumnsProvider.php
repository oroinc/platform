<?php

namespace Oro\Bundle\DataGridBundle\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;

/**
 * Returns array of field names (used in renderable columns) which must be present in select statement of datasource
 * query.
 */
class SelectedFieldsFromColumnsProvider extends AbstractSelectedFieldsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getState(DatagridConfiguration $datagridConfiguration, ParameterBag $datagridParameters): array
    {
        $state = parent::getState($datagridConfiguration, $datagridParameters);

        return array_filter(
            $state,
            function (array $columnState) {
                return $columnState[ColumnsStateProvider::RENDER_FIELD_NAME];
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration(DatagridConfiguration $datagridConfiguration): array
    {
        return (array)$datagridConfiguration->offsetGet(Configuration::COLUMNS_KEY);
    }
}
