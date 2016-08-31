<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

class ImportAction extends ImportExportAction
{
    /**
     * @var array
     */
    protected $requiredOptions = [
        'importProcessor',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return self::TYPE_IMPORT;
    }
}
