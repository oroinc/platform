<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * This provider calls all registered child providers to get widgets
 * and returns merged and ordered by priority widgets.
 */
class ChainWidgetProvider implements WidgetProviderInterface
{
    /** @var iterable|WidgetProviderInterface[] */
    private $providers;

    /**
     * @param iterable|WidgetProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return true;
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
                foreach ($widgets as $widget) {
                    $priority = $widget['priority'] ?? 0;
                    unset($widget['priority']);
                    $result[$priority][] = $widget;
                }
            }
        }

        // sort by priority and flatten
        if ($result) {
            ksort($result);
            $result = array_merge(...array_values($result));
        }

        return $result;
    }
}
