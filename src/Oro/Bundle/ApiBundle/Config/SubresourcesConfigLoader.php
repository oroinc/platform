<?php

namespace Oro\Bundle\ApiBundle\Config;

class SubresourcesConfigLoader extends AbstractConfigLoader
{
    /** @var array */
    protected $methodMap = [
        SubresourceConfig::EXCLUDE => 'setExcluded',
    ];

    /** @var array */
    protected $actionMethodMap = [
        ActionConfig::EXCLUDE => 'setExcluded',
    ];

    /** @var ActionsConfigLoader */
    protected $actionsConfigLoader;

    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $subresources = new SubresourcesConfig();
        foreach ($config as $key => $value) {
            if (!empty($value)) {
                $subresources->addSubresource($key, $this->loadSubresource($value));
            }
        }

        return $subresources;
    }

    /**
     * @param array $config
     *
     * @return SubresourceConfig
     */
    protected function loadSubresource(array $config)
    {
        $subresource = new SubresourceConfig();
        foreach ($config as $key => $value) {
            if (SubresourceConfig::ACTIONS === $key) {
                $this->loadActions($subresource, $value);
            } else {
                $this->loadConfigValue($subresource, $key, $value, $this->methodMap);
            }
        }

        return $subresource;
    }

    /**
     * @param SubresourceConfig $subresource
     * @param array|null        $actions
     */
    protected function loadActions(SubresourceConfig $subresource, array $actions = null)
    {
        if (!empty($actions)) {
            if (null === $this->actionsConfigLoader) {
                $this->actionsConfigLoader = new ActionsConfigLoader();
            }
            $actionsConfig = $this->actionsConfigLoader->load($actions);
            if (!$actionsConfig->isEmpty()) {
                foreach ($actionsConfig->getActions() as $actionName => $actionConfig) {
                    $subresource->addAction($actionName, $actionConfig);
                }
            }
        }
    }
}
