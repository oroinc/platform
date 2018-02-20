<?php

namespace Oro\Bundle\UIBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;
use Oro\Bundle\UIBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This provider calls all registered leaf providers in a chain, merges and does grouping of widgets returned
 * by each leaf provider and orders result widgets by priority.
 */
class GroupingChainWidgetProvider implements WidgetProviderInterface
{
    /** @var array [WidgetProviderInterface, group] */
    protected $providers = [];

    /** @var LabelProviderInterface */
    protected $groupNameProvider;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param LabelProviderInterface   $groupNameProvider
     * @param EventDispatcherInterface $eventDispatcher
     * @param int|null                 $pageType
     */
    public function __construct(
        LabelProviderInterface $groupNameProvider = null,
        EventDispatcherInterface $eventDispatcher = null,
        $pageType = null
    ) {
        $this->groupNameProvider = $groupNameProvider;
        $this->eventDispatcher   = $eventDispatcher;
        $this->pageType          = $pageType;
    }

    /**
     * Registers the given provider in the chain
     *
     * @param WidgetProviderInterface $provider
     * @param string|null             $group
     */
    public function addProvider(WidgetProviderInterface $provider, $group = null)
    {
        $this->providers[] = [$provider, $group];
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
     *
     * The format of returning array:
     *      [group name] =>
     *          'widgets' => array
     */
    public function getWidgets($object)
    {
        $widgets = $this->getWidgetsOrderedByPriority($object);

        if ($this->eventDispatcher) {
            $beforeGroupingChainWidgetEvent = new BeforeGroupingChainWidgetEvent($this->pageType, $widgets, $object);
            $this->eventDispatcher->dispatch(Events::BEFORE_GROUPING_CHAIN_WIDGET, $beforeGroupingChainWidgetEvent);
            $widgets = $beforeGroupingChainWidgetEvent->getWidgets();
        }

        $result = [];
        foreach ($widgets as $widget) {
            if (isset($widget['group'])) {
                $groupName = $widget['group'];
                unset($widget['group']);
            } else {
                $groupName = '';
            }
            if (!isset($result[$groupName])) {
                $result[$groupName] = [
                    'widgets' => []
                ];
                if ($this->groupNameProvider && !empty($groupName)) {
                    $result[$groupName]['label'] = $this->groupNameProvider->getLabel(
                        [
                            'groupName'   => $groupName,
                            'entityClass' => ClassUtils::getClass($object)
                        ]
                    );
                }
            }

            $result[$groupName]['widgets'][] = $widget;
        }

        return $result;
    }

    /**
     * Returns widgets ordered by priority
     *
     * @param object $object The object
     *
     * @return array
     */
    public function getWidgetsOrderedByPriority($object)
    {
        $result = [];

        // collect widgets
        foreach ($this->providers as $item) {
            /** @var WidgetProviderInterface $provider */
            $provider = $item[0];
            /** @var string|null $group */
            $group = $item[1];

            if ($provider->supports($object)) {
                $widgets = $provider->getWidgets($object);
                if (!empty($widgets)) {
                    foreach ($widgets as $widget) {
                        if (!empty($group) && !isset($widget['group'])) {
                            $widget['group'] = $group;
                        }
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
