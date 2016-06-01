<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

/**
 * Interface GuesserInterface
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
interface GuesserInterface
{
    /**
     * @param string $columnName
     * @param string $entityName
     * @param array $column
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    public function guessColumnOptions($columnName, $entityName, $column, DatagridConfiguration $config);
}
