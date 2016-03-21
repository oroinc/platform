<?php

namespace Oro\Bundle\ApiBundle\Config;

class ActionsConfigLoader extends AbstractConfigLoader
{
    /** @var array */
    protected $methodMap = [
        ActionConfig::EXCLUDE => 'setExcluded',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $actions = new ActionsConfig();
        foreach ($config as $key => $value) {
            if (!empty($value)) {
                $actions->addAction($key, $this->loadAction($value));
            }
        }

        return $actions;
    }

    /**
     * @param array $config
     *
     * @return ActionConfig
     */
    protected function loadAction(array $config)
    {
        $action = new ActionConfig();
        foreach ($config as $key => $value) {
            $this->loadConfigValue($action, $key, $value, $this->methodMap);
        }

        return $action;
    }
}
