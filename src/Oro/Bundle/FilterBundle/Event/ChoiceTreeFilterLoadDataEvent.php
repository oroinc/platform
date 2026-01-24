<?php

namespace Oro\Bundle\FilterBundle\Event;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when loading data for choice tree filters.
 *
 * This event is triggered during the data loading phase of choice tree filters,
 * allowing listeners to customize or augment the data that will be displayed in
 * the filter's tree structure. Listeners can modify the loaded data before it is
 * rendered to the user, enabling dynamic data transformation and filtering.
 */
class ChoiceTreeFilterLoadDataEvent extends Event
{
    const EVENT_NAME = 'oro_filter.choice_tree_filter_load_data';

    /** @var String */
    protected $className;

    /** @var array */
    protected $values;

    /** @var array */
    protected $data;

    /**
     * ChoiceTreeFilterLoadDataEvent constructor.
     *
     * @param string $className
     * @param array $values
     */
    public function __construct($className, array $values)
    {
        $this->className = $className;
        $this->values = $values;
    }

    /**
     * Return array id of entity
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return WidgetOptionBag
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
