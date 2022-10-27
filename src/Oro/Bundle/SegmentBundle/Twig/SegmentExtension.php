<?php

namespace Oro\Bundle\SegmentBundle\Twig;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve segment query builder configuration:
 *   - update_segment_widget_options
 *   - update_segment_condition_builder_options
 */
class SegmentExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('update_segment_widget_options', [$this, 'updateSegmentWidgetOptions']),
            new TwigFunction(
                'update_segment_condition_builder_options',
                [$this, 'updateSegmentConditionBuilderOptions']
            ),
        ];
    }

    /**
     * @param array $widgetOptions
     * @param string|null $type
     *
     * @return array
     */
    public function updateSegmentWidgetOptions(array $widgetOptions, $type = null)
    {
        $eventDispatcher = $this->getEventDispatcher();

        if (!$eventDispatcher->hasListeners(WidgetOptionsLoadEvent::EVENT_NAME)) {
            return $widgetOptions;
        }

        $event = new WidgetOptionsLoadEvent($widgetOptions, $type);
        $eventDispatcher->dispatch($event, WidgetOptionsLoadEvent::EVENT_NAME);

        return $event->getWidgetOptions();
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function updateSegmentConditionBuilderOptions(array $options)
    {
        $eventDispatcher = $this->getEventDispatcher();

        if (!$eventDispatcher->hasListeners(ConditionBuilderOptionsLoadEvent::EVENT_NAME)) {
            return $options;
        }

        $event = new ConditionBuilderOptionsLoadEvent($options);
        $eventDispatcher->dispatch($event, ConditionBuilderOptionsLoadEvent::EVENT_NAME);

        return $event->getOptions();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            EventDispatcherInterface::class,
        ];
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get(EventDispatcherInterface::class);
    }
}
