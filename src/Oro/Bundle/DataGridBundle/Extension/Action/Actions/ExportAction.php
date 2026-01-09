<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

/**
 * Represents an export action for datagrid data.
 *
 * This action allows users to export datagrid data to various formats (CSV, Excel, etc.)
 * using configured export processors.
 */
class ExportAction extends AbstractImportExportAction
{
    /**
     * @var array
     */
    protected $requiredOptions = [
        'exportProcessor',
    ];

    #[\Override]
    protected function getType()
    {
        return self::TYPE_EXPORT;
    }
}
