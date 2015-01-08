<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

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

    public function getOptions()
    {
        $options = parent::getOptions();
        $finalOptions = array_replace_recursive(self::$defaultOptions, $options->toArray());
        $options->merge($finalOptions);

        return $options;
    }
}
