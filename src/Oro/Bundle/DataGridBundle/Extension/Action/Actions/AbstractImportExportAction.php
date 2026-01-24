<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

/**
 * Provides common functionality for import/export datagrid actions.
 *
 * This base class configures the frontend type and action type for import/export operations.
 * Subclasses must specify whether they represent an import or export action by implementing the `getType` method.
 */
abstract class AbstractImportExportAction extends AbstractAction
{
    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';

    /**
     * @return string
     */
    abstract protected function getType();

    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();

        $options->merge([
            'frontend_type' => 'row/importexport',
            'type' => $this->getType(),
        ]);

        return $options;
    }
}
