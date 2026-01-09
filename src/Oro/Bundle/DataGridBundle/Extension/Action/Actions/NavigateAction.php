<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

/**
 * Represents a navigation action for datagrid rows.
 *
 * This action navigates the user to a different page, typically used for viewing or editing
 * the selected row's entity. It supports customizable launcher options for controlling
 * navigation behavior.
 */
class NavigateAction extends AbstractAction
{
    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    protected static $defaultOptions = [
        'launcherOptions' => [
            'onClickReturnValue' => false,
            'runAction'          => true,
            'className'          => 'no-hash',
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
