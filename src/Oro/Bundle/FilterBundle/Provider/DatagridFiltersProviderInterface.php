<?php

namespace Oro\Bundle\FilterBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;

/**
 * Interface for the datagrid filters providers.
 */
interface DatagridFiltersProviderInterface
{
    /**
     * Returns the list of enabled and initialized filters for the specified datagrid config.
     *
     * @param DatagridConfiguration $gridConfig
     * @return FilterInterface[]
     */
    public function getDatagridFilters(DatagridConfiguration $gridConfig): array;
}
