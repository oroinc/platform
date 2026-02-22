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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
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

    public function updateSegmentConditionBuilderOptions(array $options): array
    {
        $eventDispatcher = $this->getEventDispatcher();

        if (!$eventDispatcher->hasListeners(ConditionBuilderOptionsLoadEvent::EVENT_NAME)) {
            return $options;
        }

        $event = new ConditionBuilderOptionsLoadEvent($options);
        $eventDispatcher->dispatch($event, ConditionBuilderOptionsLoadEvent::EVENT_NAME);

        return $event->getOptions();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            EventDispatcherInterface::class
        ];
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get(EventDispatcherInterface::class);
    }
}
