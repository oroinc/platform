<?php

namespace Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * The interface for the entity ids readers which used in datagrid export.
 */
interface FilteredEntityIdentityReaderInterface
{
    /**
     * @param DatagridInterface $datagrid
     * @param string $className
     * @param array $options Contains datagrid export options
     *                       [filteredResultsGrid => gridname, filteredResultsGridParams => params, ...]
     * @return mixed
     */
    public function isApplicable(DatagridInterface $datagrid, string $className, array $options): bool;

    /**
     * @param DatagridInterface $datagrid
     * @param string $className
     * @param array $options Contains datagrid export options
     *                       [filteredResultsGrid => gridname, filteredResultsGridParams => params, ...]
     * @return array
     */
    public function getIds(DatagridInterface $datagrid, string $className, array $options): array;
}
