<?php

namespace Oro\Bundle\AttachmentBundle\DataGrid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

class DialogAction extends AbstractAction
{
    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    protected static $defaultOptions = [
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
        $finalOptions = array_replace_recursive(self::$defaultOptions, $options->toArray());
        $options->merge($finalOptions);

        return $options;
    }
}
