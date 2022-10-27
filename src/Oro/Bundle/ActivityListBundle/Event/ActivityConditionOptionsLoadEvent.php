<?php

namespace Oro\Bundle\ActivityListBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ActivityConditionOptionsLoadEvent extends Event
{
    const EVENT_NAME = 'oro_activity_list.activity_condition_options_load';

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
