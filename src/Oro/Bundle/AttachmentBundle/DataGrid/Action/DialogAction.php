<?php

namespace Oro\Bundle\AttachmentBundle\DataGrid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

class DialogAction extends AbstractAction
{
    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    protected static $additionalOptions = [
        'launcherOptions' => [
            'onClickReturnValue' => true,
            'runAction'          => true,
            'className'          => 'no-hash',
            'widget'             => [],
            'messages'           => []
        ]
    ];

    public function getOptions()
    {
        $options = parent::getOptions();
        $options->merge(self::$additionalOptions);

        return $options;
    }
}
