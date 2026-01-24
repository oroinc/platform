<?php

namespace Oro\Bundle\ActivityListBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatches activity condition options for customization.
 *
 * This event allows listeners to modify the available activity condition options
 * that are used in activity list filtering and querying. Listeners can add, remove,
 * or modify options before they are used in the application.
 */
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
