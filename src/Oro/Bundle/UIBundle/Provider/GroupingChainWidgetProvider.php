<?php

namespace Oro\Bundle\UIBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;
use Oro\Bundle\UIBundle\Event\Events;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This provider calls all registered child providers to get widgets
 * and returns merged, grouped and ordered by priority widgets.
 */
class GroupingChainWidgetProvider implements WidgetProviderInterface
{
    /** @var array [[provider id, group], ...] */
    private $providers;

    /** @var ContainerInterface */
    private $providerContainer;

    /** @var int */
    private $pageType;

    /** @var LabelProviderInterface|null */
    private $groupNameProvider;

    /** @var EventDispatcherInterface|null */
    private $eventDispatcher;

    /**
     * @param array                         $providers
     * @param ContainerInterface            $providerContainer
     * @param LabelProviderInterface|null   $groupNameProvider
     * @param EventDispatcherInterface|null $eventDispatcher
     * @param int                           $pageType
     */
    public function __construct(
        array $providers,
        ContainerInterface $providerContainer,
        LabelProviderInterface $groupNameProvider = null,
        EventDispatcherInterface $eventDispatcher = null,
        int $pageType = null
    ) {
        $this->providers = $providers;
        $this->providerContainer = $providerContainer;
        $this->groupNameProvider = $groupNameProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->pageType = $pageType;
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
            $this->eventDispatcher->dispatch($beforeGroupingChainWidgetEvent, Events::BEFORE_GROUPING_CHAIN_WIDGET);
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
                if (null !== $this->groupNameProvider && $groupName) {
                    $result[$groupName]['label'] = $this->groupNameProvider->getLabel([
                        'groupName'   => $groupName,
                        'entityClass' => ClassUtils::getClass($object)
                    ]);
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
        foreach ($this->providers as list($providerId, $group)) {
            /** @var WidgetProviderInterface $provider */
            $provider = $this->providerContainer->get($providerId);
            if ($provider->supports($object)) {
                $widgets = $provider->getWidgets($object);
                foreach ($widgets as $widget) {
                    if ($group && !isset($widget['group'])) {
                        $widget['group'] = $group;
                    }
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
