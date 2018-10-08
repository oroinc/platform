<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Describes a provider which has to return datagrid columns from given import-export context.
 */
interface DatagridColumnsFromContextProviderInterface
{
    /**
     * Returns columns from import-export context.
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    public function getColumnsFromContext(ContextInterface $context);
}
