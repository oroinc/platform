<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * This provider calls all registered leaf providers in a chain, merges widgets returned by each leaf provider
 * and orders result widgets by priority.
 */
class ChainWidgetProvider implements WidgetProviderInterface
{
    /**
     * @var WidgetProviderInterface[]
     */
    protected $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param WidgetProviderInterface $provider
     */
    public function addProvider(WidgetProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return !empty($this->providers);
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($object)
    {
        $result = [];

        // collect widgets
        foreach ($this->providers as $provider) {
            if ($provider->supports($object)) {
                $widgets = $provider->getWidgets($object);
                if (!empty($widgets)) {
                    foreach ($widgets as $widget) {
                        $priority = isset($widget['priority']) ? $widget['priority'] : 0;
                        unset($widget['priority']);
                        $result[$priority][] = $widget;
                    }
                }
            }
        }

        // sort by priority and flatten
        if (!empty($result)) {
            ksort($result);
            $result = call_user_func_array('array_merge', $result);
        }

        return $result;
    }
}
