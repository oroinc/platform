<?php

namespace Oro\Bundle\ActivityBundle\Provider;

class ChainActivityWidgetProvider implements ActivityWidgetProviderInterface
{
    /**
     * @var ActivityWidgetProviderInterface[]
     */
    protected $providers = [];

    /**
     * Registers the given provider in the chain
     *
     * @param ActivityWidgetProviderInterface $provider
     */
    public function addProvider(ActivityWidgetProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgets($entity)
    {
        $result = [];

        // collect widgets
        foreach ($this->providers as $provider) {
            if ($provider->supports($entity)) {
                $widgets = $provider->getWidgets($entity);
                foreach ($widgets as $widget) {
                    $priority = isset($widget['priority']) ? $widget['priority'] : 0;
                    unset($widget['priority']);
                    $result[$priority][] = $widget;
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
