<?php

namespace Oro\Bundle\SegmentBundle\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Twig_Extension;
use Twig_SimpleFunction;

use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;

class SegmentExtension extends Twig_Extension
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
            new Twig_SimpleFunction('update_segment_widget_options', [$this, 'updateSegmentWidgetOptions']),
        ];
    }

    /**
     * @param array $widgetOptions
     *
     * @return array
     */
    public function updateSegmentWidgetOptions(array $widgetOptions)
    {
        if (!$this->dispatcher->hasListeners(WidgetOptionsLoadEvent::EVENT_NAME)) {
            return $widgetOptions;
        }

        $event = new WidgetOptionsLoadEvent($widgetOptions);
        $this->dispatcher->dispatch(WidgetOptionsLoadEvent::EVENT_NAME, $event);

        return $event->getWidgetOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
