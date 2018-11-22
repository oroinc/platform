<?php

namespace Oro\Bundle\DataGridBundle\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

/**
 * Describes providers that must return an array of field names which must be present in select statement of
 * datasource query according to datagrid configuration and parameters.
 */
interface SelectedFieldsProviderInterface
{
    /**
     * @param DatagridConfiguration $datagridConfiguration
     * @param ParameterBag $datagridParameters
     *
     * @return array
     */
    public function getSelectedFields(
        DatagridConfiguration $datagridConfiguration,
        ParameterBag $datagridParameters
    ): array;
}
