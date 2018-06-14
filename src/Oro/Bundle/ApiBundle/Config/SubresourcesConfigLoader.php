<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The loader for "subresources" configuration section.
 */
class SubresourcesConfigLoader extends AbstractConfigLoader
{
    private const METHOD_MAP = [
        ConfigUtil::EXCLUDE => 'setExcluded'
    ];

    /** @var ActionsConfigLoader */
    private $actionsConfigLoader;

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
            if (ConfigUtil::ACTIONS === $key) {
                $this->loadActions($subresource, $value);
            } else {
                $this->loadConfigValue($subresource, $key, $value, self::METHOD_MAP);
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
