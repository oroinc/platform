<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

/**
 * Represents an import action for datagrid data.
 *
 * This action allows users to import data into the system using configured import processors,
 * typically from files in various formats (CSV, Excel, etc.).
 */
class ImportAction extends AbstractImportExportAction
{
    /**
     * @var array
     */
    protected $requiredOptions = [
        'importProcessor',
    ];

    #[\Override]
    protected function getType()
    {
        return self::TYPE_IMPORT;
    }
}
