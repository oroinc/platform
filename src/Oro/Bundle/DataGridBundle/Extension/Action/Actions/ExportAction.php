<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

class ExportAction extends ImportExportAction
{
    /**
     * @var array
     */
    protected $requiredOptions = [
        'exportProcessor',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return self::ACTION_EXPORT;
    }
}
