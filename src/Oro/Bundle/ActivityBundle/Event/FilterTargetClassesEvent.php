<?php

namespace Oro\Bundle\ActivityBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event allow to change aliases config before the usage in context search
 */
class FilterTargetClassesEvent extends Event
{
    const EVENT_NAME = 'oro_activity.filter_target_classes';

    /** @var array */
    protected $filters;

    /** @var array */
    protected $targetClasses;

    /**
     * @param array $filters
     * @param array $targetClasses
     */
    public function __construct($filters, $targetClasses)
    {
        $this->filters = $filters;
        $this->targetClasses = $targetClasses;
    }

    /**
     * Return filters array
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set the filters array
     *
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * Set target classes array
     * @param array $targetClasses
     *
     * @return array
     */
    public function setTargetClasses($targetClasses)
    {
        $this->targetClasses = $targetClasses;
    }

    /**
     * Return target classes array
     *
     * @return array
     */
    public function getTargetClasses()
    {
        return $this->targetClasses;
    }
}
