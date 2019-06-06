<?php

namespace Oro\Bundle\SegmentBundle\Twig;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve segment query builder configuration:
 *   - update_segment_widget_options
 *   - update_segment_condition_builder_options
 */
class SegmentExtension extends AbstractExtension
{
    const NAME = 'oro_segment';

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
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
        if (!$this->dispatcher->hasListeners(WidgetOptionsLoadEvent::EVENT_NAME)) {
            return $widgetOptions;
        }

        $event = new WidgetOptionsLoadEvent($widgetOptions, $type);
        $this->dispatcher->dispatch(WidgetOptionsLoadEvent::EVENT_NAME, $event);

        return $event->getWidgetOptions();
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function updateSegmentConditionBuilderOptions(array $options)
    {
        if (!$this->dispatcher->hasListeners(ConditionBuilderOptionsLoadEvent::EVENT_NAME)) {
            return $options;
        }

        $event = new ConditionBuilderOptionsLoadEvent($options);
        $this->dispatcher->dispatch(ConditionBuilderOptionsLoadEvent::EVENT_NAME, $event);

        return $event->getOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
