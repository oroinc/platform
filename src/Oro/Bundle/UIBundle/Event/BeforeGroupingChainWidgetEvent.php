<?php

namespace Oro\Bundle\UIBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class BeforeGroupingChainWidgetEvent extends Event
{
    /** @var array */
    protected $widgets;

    /** @var string */
    protected $pageType;

    /** @var object */
    protected $entity;

    /**
     * @param int    $pageType
     * @param array  $widgets
     * @param object $entity
     */
    public function __construct($pageType, array $widgets, $entity)
    {
        $this->widgets  = $widgets;
        $this->pageType = $pageType;
        $this->entity   = $entity;
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        return $this->widgets;
    }

    /**
     * @param array $widgets
     */
    public function setWidgets(array $widgets)
    {
        $this->widgets = $widgets;
    }

    /**
     * @return int
     */
    public function getPageType()
    {
        return $this->pageType;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
