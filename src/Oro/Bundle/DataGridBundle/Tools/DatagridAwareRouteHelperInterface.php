<?php

namespace Oro\Bundle\DataGridBundle\Tools;

interface DatagridAwareRouteHelperInterface
{
    /**
     * Generates URL or URI for the Datagrid filtered by parameters
     *
     * @param array  $params
     * @param int    $referenceType
     *
     * @return string
     */
    public function generate(array $params, $referenceType);
}
