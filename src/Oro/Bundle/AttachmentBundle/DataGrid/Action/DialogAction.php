<?php

namespace Oro\Bundle\AttachmentBundle\DataGrid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

/**
 * Provides a dialog action for attachment-related data grid operations.
 *
 * This action extends the base {@see AbstractAction} to support launching dialogs with customizable
 * launcher options, including widget configuration and event handling. It is used to display
 * attachment-related dialogs in data grids with configurable behavior for click events and
 * widget initialization.
 */
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

    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();
        $finalOptions = array_replace_recursive(self::$defaultOptions, $options->toArray());
        $options->merge($finalOptions);

        return $options;
    }
}
