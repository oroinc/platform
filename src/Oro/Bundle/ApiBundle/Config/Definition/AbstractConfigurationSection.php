<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

abstract class AbstractConfigurationSection
{
    /**
     * @param NodeBuilder $node
     * @param array       $callbacks
     * @param string      $section
     *
     * @return array
     */
    protected function callConfigureCallbacks(NodeBuilder $node, array $callbacks, $section)
    {
        if (isset($callbacks[$section])) {
            foreach ($callbacks[$section] as $callback) {
                call_user_func($callback, $node);
            }
        }
    }

    /**
     * @param array|null $config
     * @param array      $callbacks
     * @param string     $section
     *
     * @return array
     */
    protected function callProcessConfigCallbacks($config, array $callbacks, $section)
    {
        if (isset($callbacks[$section])) {
            foreach ($callbacks[$section] as $callback) {
                $config = call_user_func($callback, $config);
            }
        }

        return $config;
    }
}
