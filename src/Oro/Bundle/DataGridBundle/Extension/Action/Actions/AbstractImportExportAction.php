<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

abstract class AbstractImportExportAction extends AbstractAction
{
    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';

    /**
     * @return string
     */
    abstract protected function getType();

    /**
     * {@inheritdoc}
     */
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
