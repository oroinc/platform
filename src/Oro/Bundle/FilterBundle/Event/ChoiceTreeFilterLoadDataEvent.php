<?php

namespace Oro\Bundle\FilterBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

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

    /**
     * @param $values
     */
    public function setData($values)
    {
        $this->data = $values;
    }
}
