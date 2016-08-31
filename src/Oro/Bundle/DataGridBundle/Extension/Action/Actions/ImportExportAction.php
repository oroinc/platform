<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

abstract class ImportExportAction extends AbstractAction
{
    const ACTION_IMPORT = 'import';
    const ACTION_EXPORT = 'export';

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
            'frontend_type' => 'importexport',
            'type' => $this->getType(),
        ]);

        return $options;
    }
}
