<?php

namespace Oro\Bundle\SegmentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ConditionBuilderOptionsLoadEvent extends Event
{
    const EVENT_NAME = 'oro_segment.condition_builder_options_load';

    /** @var array */
    protected $options;

    /**
     * @param array $options
     */
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

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }
}
