<?php

namespace Oro\Bundle\ApiBundle\Config;

class ActionsConfigLoader extends AbstractConfigLoader
{
    /** @var array */
    protected $methodMap = [
        ActionConfig::EXCLUDE => 'setExcluded',
    ];

    /** @var StatusCodesConfigLoader */
    private $statusCodesConfigLoader;

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
            if (ActionConfig::STATUS_CODES === $key) {
                $this->loadStatusCodes($action, $value);
            } else {
                $this->loadConfigValue($action, $key, $value, $this->methodMap);
            }
        }

        return $action;
    }

    /**
     * @param ActionConfig $action
     * @param array        $statusCodes
     */
    protected function loadStatusCodes(ActionConfig $action, array $statusCodes)
    {
        if (!empty($statusCodes)) {
            if (null === $this->statusCodesConfigLoader) {
                $this->statusCodesConfigLoader = new StatusCodesConfigLoader();
            }
            $action->setStatusCodes($this->statusCodesConfigLoader->load($statusCodes));
        }
    }
}
