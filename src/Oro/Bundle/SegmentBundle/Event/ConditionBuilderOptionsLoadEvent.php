<?php

namespace Oro\Bundle\SegmentBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when loading condition builder options for segments.
 *
 * This event allows listeners to modify or extend the options available in the segment
 * condition builder interface. Listeners can add custom condition types, operators, or
 * other configuration options that should be available when building segment conditions.
 * The event carries the current options array which can be read and modified by listeners.
 */
class ConditionBuilderOptionsLoadEvent extends Event
{
    const EVENT_NAME = 'oro_segment.condition_builder_options_load';

    /** @var array */
    protected $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }
}
