<?php

namespace Oro\Bundle\ApiBundle\Config;


class ActionsConfigLoader extends AbstractConfigLoader implements ConfigLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $actions = new ActionsConfig();
        foreach ($config as $key => $value) {
            $actions->set($key, $value);
        }

        return $actions;
    }
}
