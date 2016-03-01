<?php

namespace Oro\Bundle\DashboardBundle\EventListener;

use Oro\Bundle\DashboardBundle\Event\WidgetItemsLoadDataEvent;

class WidgetItemsLoadDataListener
{
    /**
     * @param WidgetItemsLoadDataEvent $event
     */
    public function filterItemsByItemsChoice(WidgetItemsLoadDataEvent $event)
    {
        $widgetConfig = $event->getWidgetConfig();
        if (!isset($widgetConfig['configuration'], $widgetConfig['configuration']['subWidgets'])) {
            return;
        }

        if ($widgetConfig['configuration']['subWidgets']['type'] !== 'oro_type_widget_items_choice') {
            return;
        }

        $visibleItems = $event->getWidgetOptions()->get('subWidgets') ? : [];
        $items        = $event->getItems();
        $event->setItems(array_intersect_key($items, array_flip($visibleItems)));
    }

    /**
     * @param WidgetItemsLoadDataEvent $event
     */
    public function filterItems(WidgetItemsLoadDataEvent $event)
    {
        $widgetConfig = $event->getWidgetConfig();
        if (!isset($widgetConfig['configuration'], $widgetConfig['configuration']['subWidgets'])) {
            return;
        }

        $widgetOptions = $event->getWidgetOptions();
        $config        = $widgetOptions->get('subWidgets', []);
        if (!isset($config['items'])) {
            return;
        }

        $configItems = $this->getSortedConfigItems($config);
        $items       = $this->sortItemsByConfigItems($event->getItems(), $configItems);

        $event->setItems($items);
    }

    /**
     * @param array $configuration
     *
     * @return array
     */
    protected function getSortedConfigItems(array $configuration)
    {
        $items = [];
        foreach ($configuration['items'] as $item) {
            if (!$item['show']) {
                continue;
            }

            $items[$item['id']] = $item;
        }
        uasort($items, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $items;
    }

    /**
     * @param array $items
     * @param array $sortedConfigItems
     *
     * @return array
     */
    protected function sortItemsByConfigItems(array $items, array $sortedConfigItems)
    {
        $result = array_intersect_key($items, $sortedConfigItems);

        $sortedKeys = array_flip(array_keys($sortedConfigItems));
        uksort($result, function ($a, $b) use ($sortedKeys) {
            return $sortedKeys[$a] - $sortedKeys[$b];
        });

        return $result;
    }
}
