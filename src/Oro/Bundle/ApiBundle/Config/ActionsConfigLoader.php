<?php

namespace Oro\Bundle\ApiBundle\Config;

class ActionsConfigLoader extends AbstractConfigLoader
{
    /** @var array */
    protected $methodMap = [
        ActionConfig::EXCLUDE      => 'setExcluded',
        ActionConfig::ACL_RESOURCE => 'setAclResource',
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
            if (isset($this->methodMap[$key])) {
                $this->callSetter($action, $this->methodMap[$key], $value);
            } else {
                $this->setValue($action, $key, $value);
            }
        }

        return $action;
    }
}
