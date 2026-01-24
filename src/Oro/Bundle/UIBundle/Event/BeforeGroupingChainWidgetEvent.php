<?php

namespace Oro\Bundle\UIBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before rendering grouped chain widgets.
 *
 * This event allows listeners to modify the widgets that will be grouped and rendered
 * for a specific page type and entity. It provides access to the widgets array, page type,
 * and the entity being rendered, enabling customization of widget grouping behavior.
 */
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
